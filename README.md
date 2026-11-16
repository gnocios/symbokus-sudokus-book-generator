# symbokus-sudokus-book-generator

Generates printable sudoku books as PDF with themed symbol sets (Braille, Roman numerals, music notes, I Ching and more), difficulty levels and solutions. Original PHP version, a cleaned-up PHP version and a Python version.

## Symbol sets

| Id | Symbols |
|---|---|
| 0 | 1 2 3 4 5 6 7 8 9 |
| 1 | A B C D E F G b # |
| 2 | do re mi fa sol la si b # |
| 3 | Braille |
| 4 | Yin Yang (I Ching trigrams) |
| 5 | Roman numerals |
| 6 | Asian numerals |

Difficulty goes from 1 (Rookie) to 5 (Master), or `progression`, which raises the level every 10 puzzles. Each book has the puzzles first and the solutions at the end.

## Project structure

```
original/   first PHP version (mPDF), kept as the starting point
review/     the same generator rewritten in PHP
python/     Python version (ReportLab), command line
```

## Original (PHP)

`original/symbokuGeneratorPure.php` is the first version I wrote while learning. It works, but it uses global variables, nested `array_splice` loops and a fixed output path. I kept it unchanged to show the starting point. It was also tried with Dompdf, Fpdf and Tcpdf before settling on mPDF.

```bash
cd original
composer install
```

Serve the folder with PHP (for example `php -S localhost:8000`) and open `symbokuGeneratorPure.php` in the browser.

## Review (PHP)

`review/symbokuGenerator.php` is the same generator rewritten: no globals, a recursive backtracking solver, validated form input, and the PDF written to `output/`, which is created if missing.

```bash
cd review
composer install
php -S localhost:8000
```

Open `http://localhost:8000/symbokuGenerator.php`.

## Python

```bash
cd python
pip install -r requirements.txt
python symbokus.py -q 10 -d progression -s random
```

The PDF is written to `python/output/symbokus.pdf`.

| Option | Meaning |
|---|---|
| `-q`, `--quantity` | number of puzzles (1 to 100) |
| `-d`, `--difficulty` | `1` to `5`, or `progression` |
| `-s`, `--symbols` | `0` to `6`, or `random` |
| `-o`, `--output` | output path |

### Fonts

The script looks for fonts in `C:/Windows/Fonts/` (Segoe UI Symbol and MS Gothic), then in common Linux and macOS paths. They are not included in the repo. If a symbol set shows empty squares, edit `FONT_CANDIDATES` in `symbokus.py` with the path of a `.ttf` or `.ttc` font that has those glyphs.

## Known limitations

- The generator does not check that each puzzle has a unique solution. It empties random cells.
- Cover and book layout are not generated yet.