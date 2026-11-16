"""Sudoku book generator with themed symbol sets -> PDF (puzzles first, then solutions).

Usage (from the python/ folder):
    python symbokus.py --quantity 10 --difficulty progression --symbols random
    python symbokus.py -q 5 -d 3 -s 4 -o output/hard_yinyang.pdf
"""
import argparse
import os
import random

from reportlab.lib.pagesizes import A4
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.pdfmetrics import stringWidth
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas

# Each set: (symbols, font key). The CJK set needs a font with the circled ideographs.
SYMBOL_SETS = {
    0: (list("123456789"), "main"),
    1: (["A", "B", "C", "D", "E", "F", "G", "b", "#"], "main"),
    2: (["do", "re", "mi", "fa", "sol", "la", "si", "b", "#"], "main"),
    3: (list("⠁⠃⠉⠙⠑⠋⠛⠓⠊"), "main"),
    4: (list("☰☱☲☳☴☵☶☷☯"), "main"),
    5: (["I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX"], "main"),
    6: (list("㊀㊁㊂㊃㊄㊅㊆㊇㊈"), "cjk"),
}

FONT_CANDIDATES = {
    "main": [
        "C:/Windows/Fonts/seguisym.ttf",
        "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
        "/System/Library/Fonts/Supplemental/Arial Unicode.ttf",
    ],
    "cjk": [
        "C:/Windows/Fonts/msgothic.ttc",
        "C:/Windows/Fonts/YuGothM.ttc",
        "C:/Windows/Fonts/msyh.ttc",
        "/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc",
        "/System/Library/Fonts/Supplemental/Arial Unicode.ttf",
    ],
}

CELL = 50
GRID = CELL * 9
_registered = set()


def use_font(key):
    name = f"Symbokus-{key}"
    if key not in _registered:
        path = next((p for p in FONT_CANDIDATES[key] if os.path.exists(p)), None)
        if path is None:
            raise SystemExit(
                f"No font found for '{key}'. Edit FONT_CANDIDATES in this file "
                "with the path of a .ttf/.ttc font that has the needed symbols."
            )
        pdfmetrics.registerFont(TTFont(name, path))
        _registered.add(key)
    return name


def is_valid(grid, pos, symbol):
    row, col = divmod(pos, 9)
    if any(grid[row * 9 + i] == symbol or grid[i * 9 + col] == symbol for i in range(9)):
        return False
    br, bc = row // 3 * 3, col // 3 * 3
    return all(grid[(br + r) * 9 + bc + c] != symbol for r in range(3) for c in range(3))


def generate_solution(symbols):
    """Complete valid grid (81 cells) using random backtracking."""
    grid = [None] * 81

    def fill(pos):
        if pos == 81:
            return True
        candidates = symbols[:]
        random.shuffle(candidates)
        for symbol in candidates:
            if is_valid(grid, pos, symbol):
                grid[pos] = symbol
                if fill(pos + 1):
                    return True
                grid[pos] = None
        return False

    fill(0)
    return grid


def create_puzzle(solution, difficulty):
    """Empties difficulty*10+10 random cells. Uniqueness of the solution is not checked."""
    puzzle = solution[:]
    for pos in random.sample(range(81), difficulty * 10 + 10):
        puzzle[pos] = ""
    return puzzle


def draw_grid(pdf, grid, font_name, title):
    page_w, page_h = A4
    x0 = (page_w - GRID) / 2
    y0 = (page_h - GRID) / 2

    pdf.setFont("Helvetica-Bold", 16)
    pdf.drawCentredString(page_w / 2, y0 + GRID + 30, title)

    for i in range(10):
        pdf.setLineWidth(2.2 if i % 3 == 0 else 0.6)
        pdf.line(x0, y0 + i * CELL, x0 + GRID, y0 + i * CELL)
        pdf.line(x0 + i * CELL, y0, x0 + i * CELL, y0 + GRID)

    for pos, value in enumerate(grid):
        if not value:
            continue
        row, col = divmod(pos, 9)
        size = 28
        while size > 8 and stringWidth(value, font_name, size) > CELL - 8:
            size -= 1
        pdf.setFont(font_name, size)
        cx = x0 + col * CELL + CELL / 2
        cy = y0 + GRID - row * CELL - CELL / 2 - size * 0.35
        pdf.drawCentredString(cx, cy, value)

    pdf.showPage()


def build_book(quantity, difficulty, symbols, output):
    puzzles, solutions = [], []
    for i in range(quantity):
        set_id = i % len(SYMBOL_SETS) if symbols == "random" else int(symbols)
        level = (i // 10) % 5 + 1 if difficulty == "progression" else int(difficulty)
        symbol_list, font_key = SYMBOL_SETS[set_id]
        solution = generate_solution(symbol_list)
        puzzles.append((create_puzzle(solution, level), font_key, level))
        solutions.append((solution, font_key))

    os.makedirs(os.path.dirname(os.path.abspath(output)), exist_ok=True)
    pdf = canvas.Canvas(output, pagesize=A4)
    for n, (grid, font_key, level) in enumerate(puzzles, 1):
        draw_grid(pdf, grid, use_font(font_key), f"Symbokus #{n}  -  level {level}")
    for n, (grid, font_key) in enumerate(solutions, 1):
        draw_grid(pdf, grid, use_font(font_key), f"Solution #{n}")
    pdf.save()
    print("saved", output)


def main():
    parser = argparse.ArgumentParser(description="Generate a sudoku book as PDF.")
    parser.add_argument("-q", "--quantity", type=int, default=1)
    parser.add_argument("-d", "--difficulty", default="1",
                        choices=["1", "2", "3", "4", "5", "progression"],
                        help="1 Rookie, 2 Easy, 3 Medium, 4 Hard, 5 Master")
    parser.add_argument("-s", "--symbols", default="random",
                        choices=["random"] + [str(k) for k in SYMBOL_SETS],
                        help="0 digits, 1 letters, 2 solfege, 3 braille, 4 yin-yang, 5 roman, 6 asian")
    parser.add_argument("-o", "--output", default="output/symbokus.pdf")
    args = parser.parse_args()
    build_book(max(1, min(100, args.quantity)), args.difficulty, args.symbols, args.output)


if __name__ == "__main__":
    main()