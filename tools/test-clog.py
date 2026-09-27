#!/usr/bin/env python3
"""Run Clog's standalone suites on a disposable copy with the extracted packages."""
import argparse
from pathlib import Path
import shutil
import subprocess
import tempfile

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('clog', type=Path)
parser.add_argument('--image', default='localhost/clog-php:8.3-rust')
args = parser.parse_args()
workspace = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix='eleph-clog-tests-') as tmp:
    fixture = Path(tmp)
    for directory in ('server/src', 'server/generated', 'server/standalone/src',
                      'server/standalone/manifests', 'server/standalone/tests',
                      'server/standalone/public', 'server/standalone/vendor', 'client/dist'):
        shutil.copytree(args.clog / directory, fixture / directory)
    shutil.copy2(args.clog / 'server/standalone/bootstrap.php', fixture / 'server/standalone/bootstrap.php')
    for name in ('sqlite', 'graphql'):
        destination = fixture / 'server/standalone/vendor/elephentity' / name / 'src'
        shutil.rmtree(destination)
        shutil.copytree(workspace / ('elephentity-sqlite' if name == 'sqlite' else 'elephentity-graphql-php') / 'src', destination)
    for test in ('integration', 'http'):
        subprocess.run(['podman', 'run', '--rm', '--network=none', '--security-opt=label=disable',
                        '-v', f'{fixture}:/fixture:ro', '-w', '/fixture/server', args.image,
                        'php', f'standalone/tests/{test}.php'], check=True)
