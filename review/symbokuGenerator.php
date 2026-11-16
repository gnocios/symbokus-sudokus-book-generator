<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

const SYMBOL_SETS = [
    0 => ['1', '2', '3', '4', '5', '6', '7', '8', '9'],
    1 => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'b', '#'],
    2 => ['do', 're', 'mi', 'fa', 'sol', 'la', 'si', 'b', '#'],
    3 => ['⠁', '⠃', '⠉', '⠙', '⠑', '⠋', '⠛', '⠓', '⠊'],
    4 => ['☰', '☱', '☲', '☳', '☴', '☵', '☶', '☷', '☯'],
    5 => ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX'],
    6 => ['㊀', '㊁', '㊂', '㊃', '㊄', '㊅', '㊆', '㊇', '㊈'],
];

const SYMBOL_LABELS = [
    'random' => 'Random',
    0 => '123456789',
    1 => 'ABCDEFGb#',
    2 => 'DoReMi...',
    3 => 'Braille',
    4 => 'YingYang',
    5 => 'Roman numbers',
    6 => 'Asian numbers',
];

const DIFFICULTY_LABELS = [
    1 => 'Rookie',
    2 => 'Easy',
    3 => 'Medium',
    4 => 'Hard',
    5 => 'Master',
    'progression' => 'Progression',
];

/** Checks that $symbol can go in cell $pos without repeating in its row, column or 3x3 box. */
function isValid(array $grid, int $pos, string $symbol): bool
{
    $row = intdiv($pos, 9);
    $col = $pos % 9;

    for ($i = 0; $i < 9; $i++) {
        if ($grid[$row * 9 + $i] === $symbol || $grid[$i * 9 + $col] === $symbol) {
            return false;
        }
    }

    $boxRow = intdiv($row, 3) * 3;
    $boxCol = intdiv($col, 3) * 3;
    for ($r = 0; $r < 3; $r++) {
        for ($c = 0; $c < 3; $c++) {
            if ($grid[($boxRow + $r) * 9 + $boxCol + $c] === $symbol) {
                return false;
            }
        }
    }

    return true;
}

/** Builds a complete valid grid (81 cells) with random backtracking. */
function generateSolution(array $symbols): array
{
    $grid = array_fill(0, 81, null);

    $fill = function (int $pos) use (&$fill, &$grid, $symbols): bool {
        if ($pos === 81) {
            return true;
        }
        $candidates = $symbols;
        shuffle($candidates);
        foreach ($candidates as $symbol) {
            if (isValid($grid, $pos, $symbol)) {
                $grid[$pos] = $symbol;
                if ($fill($pos + 1)) {
                    return true;
                }
                $grid[$pos] = null;
            }
        }
        return false;
    };

    $fill(0);
    return $grid;
}

/** Empties (difficulty * 10 + 10) random cells. Uniqueness of the solution is not checked. */
function createPuzzle(array $solution, int $difficulty): array
{
    $positions = range(0, 80);
    shuffle($positions);
    foreach (array_slice($positions, 0, $difficulty * 10 + 10) as $pos) {
        $solution[$pos] = '';
    }
    return $solution;
}

function gridToHtml(array $grid): string
{
    $html = '';
    foreach (array_chunk($grid, 9) as $row) {
        $html .= '<tr>';
        foreach ($row as $value) {
            $text = $value === '' ? '&nbsp;' : htmlspecialchars((string) $value);
            $html .= "<td>$text</td>";
        }
        $html .= '</tr>';
    }
    return "<div class='borde'><table class='table'>$html</table></div>";
}

function createPdf(int $quantity, string $difficulty, string $symbolChoice, string $path): void
{
    $puzzlePages = [];
    $solutionPages = [];

    for ($i = 0; $i < $quantity; $i++) {
        $setId = $symbolChoice === 'random' ? $i % count(SYMBOL_SETS) : (int) $symbolChoice;
        $level = $difficulty === 'progression' ? intdiv($i, 10) % 5 + 1 : (int) $difficulty;

        $solution = generateSolution(SYMBOL_SETS[$setId]);
        $puzzle = createPuzzle($solution, $level);

        $puzzlePages[] = gridToHtml($puzzle);
        $solutionPages[] = gridToHtml($solution);
    }

    $html = implode('<pagebreak>', array_merge($puzzlePages, $solutionPages));

    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }

    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
    $mpdf->WriteHTML(file_get_contents(__DIR__ . '/symboku.css'), \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->Output($path, \Mpdf\Output\Destination::FILE);
}

$generated = null;

if (isset($_POST['generate'])) {
    $quantity = max(1, min(100, (int) ($_POST['quantity'] ?? 1)));
    $difficulty = (string) ($_POST['difficulty'] ?? '1');
    $symbolChoice = (string) ($_POST['symbols'] ?? 'random');

    if (array_key_exists($difficulty, DIFFICULTY_LABELS) && array_key_exists($symbolChoice, SYMBOL_LABELS)) {
        $generated = 'output/symbokus.pdf';
        createPdf($quantity, $difficulty, $symbolChoice, __DIR__ . '/' . $generated);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Symbokus</title>
</head>
<body>
    <form action="" method="post">
        <div>
            <label>Quantity <input type="number" name="quantity" value="1" min="1" max="100"></label>
        </div>
        <div>
            <label>Difficulty
                <select name="difficulty">
                    <?php foreach (DIFFICULTY_LABELS as $value => $label): ?>
                        <option value="<?= $value ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div>
            <label>Symbols
                <select name="symbols">
                    <?php foreach (SYMBOL_LABELS as $value => $label): ?>
                        <option value="<?= $value ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div><button type="submit" name="generate">Generate PDF</button></div>
    </form>

    <?php if ($generated): ?>
        <p><a href="<?= $generated ?>" target="_blank">Open generated PDF</a></p>
    <?php endif; ?>
</body>
</html>