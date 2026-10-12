"""Убирает из файла nginx блоки server { }, в server_name которых есть указанный домен.

Использование: python3 strip-server-blocks.py ФАЙЛ ДОМЕН
Остальные блоки и текст остаются байт в байт. Печатает, сколько блоков убрано.
"""
import re
import sys

path, domain = sys.argv[1], sys.argv[2]
text = open(path, encoding='utf-8').read()


def block_end(start):
    """Индекс сразу после закрывающей скобки блока, открытого на позиции start."""
    depth, i, n = 0, start, len(text)
    while i < n:
        c = text[i]
        if c == '#':
            i = text.find('\n', i)
            if i == -1:
                return n
            continue
        if c in '"\'':
            j = text.find(c, i + 1)
            i = n if j == -1 else j + 1
            continue
        if c == '{':
            depth += 1
        elif c == '}':
            depth -= 1
            if depth == 0:
                return i + 1
        i += 1
    raise SystemExit('не нашёл конец блока server — файл не трогаю')


out, pos, removed = [], 0, 0
for m in re.finditer(r'(?m)^[ \t]*server\s*\{', text):
    if m.start() < pos:
        continue  # вложенное совпадение внутри уже пройденного блока
    end = block_end(text.index('{', m.start()))
    block = text[m.start():end]
    names = ' '.join(re.findall(r'(?m)^\s*server_name\s+([^;]+);', block)).split()
    out.append(text[pos:m.start()])
    if domain in names or ('www.' + domain) in names:
        removed += 1
        # съедаем перевод строки после блока, чтобы не копились пустые строки
        if text[end:end + 1] == '\n':
            end += 1
    else:
        out.append(block)
    pos = end
out.append(text[pos:])

if removed:
    open(path, 'w', encoding='utf-8').write(''.join(out))
print(removed)
