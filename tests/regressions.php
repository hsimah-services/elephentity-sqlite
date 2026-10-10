<?php

declare(strict_types=1);

require $argv[1] ?? dirname(__DIR__) . '/vendor/autoload.php';
spl_autoload_register(static function (string $class): void {
    $prefix = 'Eleph\\SQLite\\';
    if (str_starts_with($class, $prefix)) {
        require dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
}, prepend: true);

use Eleph\SQLite\{Database, SQLiteAdaptor};
use Eleph\SQLite\Sql\{TableSchema, Column, FieldMap, EdgePlacement};
use Eleph\Runtime\Identity\EntityId;
use Eleph\Runtime\Storage\{Criteria, Offset, Order, RelationKind};
use Eleph\Runtime\Storage\Write\{Unlink, WriteBatch};

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

$database = new Database(':memory:');
$database->execute('CREATE TABLE item (id INTEGER PRIMARY KEY, owner_id INTEGER)');
$adaptor = new SQLiteAdaptor($database, ['Item' => new TableSchema('item', ['id' => new Column('id', 'INTEGER'), 'owner_id' => new Column('owner_id', 'INTEGER', nullable: true)])], new FieldMap([]), [
    'Item.owner' => new EdgePlacement('Item', 'owner', 'Owner', RelationKind::ManyToOne, 'item', 'owner_id'),
]);
$database->execute('INSERT INTO item (id, owner_id) VALUES (1, 2)');
$adaptor->write(new WriteBatch(new Unlink('Item', 'owner', EntityId::of(1), EntityId::of(3))));
check((int) $database->scalar('SELECT owner_id FROM item WHERE id = 1') === 2, 'Removing a different target cleared the current link.');
$adaptor->write(new WriteBatch(new Unlink('Item', 'owner', EntityId::of(1), EntityId::of(2))));
check($database->scalar('SELECT owner_id FROM item WHERE id = 1') === null, 'Removing the current target did not clear it.');
$database->execute('UPDATE item SET owner_id = 2 WHERE id = 1');
$adaptor->write(new WriteBatch(new Unlink('Item', 'owner', EntityId::of(1))));
check($database->scalar('SELECT owner_id FROM item WHERE id = 1') === null, 'Clear-all did not clear the link.');

$database->transaction(function () use ($database): void {
    for ($id = 2; $id <= 1002; ++$id) $database->execute('INSERT INTO item (id) VALUES (%d)', [$id]);
});
$criteria = Criteria::for('Item')->orderBy(Order::ascending('id'));
$first = $adaptor->query($criteria->take(2000));
check(count($first->items) === 1000 && $first->hasMore(), 'An oversized page exposed the probe row or lost the next page.');
check(Offset::fromCursor($first->next)->value === 1000, 'Cursor did not advance by the effective page size.');
$next = $adaptor->query($criteria->take(2000, $first->next));
check(array_map(static fn ($record) => (int) $record->id->raw(), $next->items) === [1001, 1002] && !$next->hasMore(), 'The final page lost or repeated rows.');
$zero = $adaptor->query($criteria->take(0));
check($zero->items === [] && !$zero->hasMore(), 'A zero page has a non-advancing cursor.');
try { $adaptor->query($criteria->take(-1)); throw new LogicException('A negative page was accepted.'); }
catch (RuntimeException $error) { check(str_contains($error->getMessage(), 'non-negative'), 'Unexpected negative-page failure.'); }
echo "PASS: targeted unlink, oversized pages, zero and negative page sizes\n";
