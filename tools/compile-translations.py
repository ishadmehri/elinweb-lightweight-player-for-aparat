"""Compile the bundled Persian catalog. Development dependency: Babel."""
from pathlib import Path
import struct
from babel.messages.pofile import read_po, write_po
from babel.messages.mofile import write_mo
from babel.messages.catalog import Catalog

directory = Path(__file__).resolve().parents[1] / 'languages'
domain = 'elinweb-lightweight-player-for-aparat'
with (directory / f'{domain}-fa_IR.po').open('rb') as source:
    catalog = read_po(source, locale='fa_IR', domain=domain)
errors = list(catalog.check())
if errors:
    raise RuntimeError(errors)
mo_path = directory / f'{domain}-fa_IR.mo'
with mo_path.open('wb') as target:
    write_mo(target, catalog)
# Babel leaves the hash-table address as zero when it omits the optional table.
# WordPress 6.3's POMO reader uses that address to find the string tables.
data = bytearray(mo_path.read_bytes())
magic, revision, total, originals_addr, translations_addr, hash_length, hash_addr = struct.unpack('<7I', data[:28])
if magic == 0x950412DE and revision == 0 and hash_length == 0 and hash_addr == 0:
    struct.pack_into('<I', data, 24, translations_addr + total * 8)
    mo_path.write_bytes(data)

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
