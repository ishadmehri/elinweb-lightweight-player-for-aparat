"""Compile the bundled Persian catalog. Development dependency: Babel."""
from pathlib import Path
from babel.messages.pofile import read_po, write_po
from babel.messages.mofile import write_mo
from babel.messages.catalog import Catalog

directory = Path(__file__).resolve().parents[1] / 'languages'
domain = 'lightweight-player-for-aparat'
with (directory / f'{domain}-fa_IR.po').open('rb') as source:
    catalog = read_po(source, locale='fa_IR', domain=domain)
errors = list(catalog.check())
if errors:
    raise RuntimeError(errors)
with (directory / f'{domain}-fa_IR.mo').open('wb') as target:
    write_mo(target, catalog)

template = Catalog(project=catalog.project, version=catalog.version, domain=domain,
                   copyright_holder='Iman Shadmehri and contributors')
for message in catalog:
    if message.id:
        template.add(message.id, locations=message.locations, context=message.context)
with (directory / f'{domain}.pot').open('wb') as target:
    write_po(target, template, width=100)
template_path = directory / f'{domain}.pot'
template_path.write_bytes(template_path.read_bytes().rstrip(b'\r\n') + b'\n')
print('Compiled Persian MO and translation template.')
