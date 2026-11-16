<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Symbokus</title>
</head>
<?php 
require_once __DIR__ .'/vendor/autoload.php';
$arroyo=array("1","2","3","4","5","6","7","8","9");
$difficulty=1;
$puzzle = [];
$panel;
$rowArray = []; //array to store content of each row
$colArray = []; //array to store content of each column
$gridArray = [];//array to store content of each grid
$cellArray = [];//array to store numbres possible for each cell
$selectores = false;
$forward = true;  // pupulation direction
$previousCell = null;
$currentCell = null; //cell under selection
$cell = [];

	if(isset($_POST["generar"])){
		$cantidad=$_POST['cantidad'];
		$diff=$_POST['dificultad'];
		$simbolos=$_POST['simbolo'];

		function setSymbol($simbolos){
			$selectores = true;
			if($simbolos == 0){$GLOBALS['arroyo'] = array("1","2","3","4","5","6","7","8","9");}
			elseif($simbolos == 1){$GLOBALS['arroyo'] = array("A","B","C","D","E","F","G","b","#");} 
			else if($simbolos == 2){$GLOBALS['arroyo'] = array("do","re","mi","fa","sol","la","si","b","#");}
			else if($simbolos == 3){$GLOBALS['arroyo'] = array("⠁","⠃","⠉","⠙","⠑","⠋","⠛","⠓", "⠊");}
			else if($simbolos == 4){$GLOBALS['arroyo'] = array("☰","☱","☲","☳","☴","☵","☶","☷","☯");}
			else if($simbolos == 5){$GLOBALS['arroyo'] = array("I","II","III","IV","V","VI","VII","VIII","IX");}
			else if ($simbolos == 6){$GLOBALS['arroyo'] = array("㊀","㊁","㊂","㊃","㊄","㊅","㊆","㊇","㊈");}
		}

		function populate() {
			for ($count = 0; $count < 9; $count++) {
				$GLOBALS['rowArray'][$count] = [];
				$GLOBALS['colArray'][$count] = [];
				$GLOBALS['gridArray'][$count] = [];
			}
			
			for ( $cellId = 0; $cellId < 81; $cellId++) {
				 $row = floor($cellId / 9);
				 $col = $cellId % 9;
				 $grid =floor(($row / 3)) * 3 + floor($col / 3);
				

				if($GLOBALS['forward']){
					$GLOBALS['cellArray'][$cellId] = $GLOBALS['arroyo'];
					$GLOBALS['cellArray'][$cellId] = subtract($GLOBALS['cellArray'][$cellId], $GLOBALS['rowArray'][$row], $GLOBALS['colArray'][$col], $GLOBALS['gridArray'][$grid]);

				}else{
					$num = $final[$cellId];

					for($i=0; $i<count($GLOBALS['rowArray'][$row]); $i++){
						if($num == $GLOBALS['rowArray'][$row][$i]){
							array_splice($GLOBALS['rowArray'][$row],$i,1);
						}
					}
		
					for( $i=0;$i<count($GLOBALS['colArray'][$col]); $i++){
						if($num == $GLOBALS['colArray'][$col][$i]){
							array_splice($GLOBALS['colArray'][$col],$i,1);
						}
					}
	
					for( $i=0;$i<count($GLOBALS['gridArray'][$grid]); $i++){
						if($num == $GLOBALS['gridArray'][$grid][$i]){
							array_splice($GLOBALS['gridArray'][$grid],$i,1);
						}
					}
					for($i=0; $i<count($GLOBALS['cellArray'][$cellId]); $i++){
						if($num == $GLOBALS['cellArray'][$cellId][$i]){
							array_splice($GLOBALS['cellArray'][$cellId],$i,1);
						}
					}
	
				}
				
				if (count($GLOBALS['cellArray'][$cellId])) {
					$index = rand(0, ( count($GLOBALS['cellArray'][$cellId]) -1 ) );
					$number = $GLOBALS['cellArray'][$cellId][$index];
					array_push($GLOBALS['rowArray'][$row], $number);
					array_push($GLOBALS['colArray'][$col], $number);
					array_push($GLOBALS['gridArray'][$grid], $number);
					$final[$cellId] = $number;
					$GLOBALS['forward'] = true;
	
				} else {
					$cellId -= 2;
					$GLOBALS['forward'] = false;
				}
			}
			return $final;
		}

		function subtract($source, $sub1, $sub2, $sub3) {
			$diffArray = $source;
			for ($i = 0; $i < count($sub1); $i++) {
				for ($j = 0; $j < count($diffArray); $j++) {
					if ($sub1[$i] == $diffArray[$j]) {
						array_splice($diffArray,$j,1);
					}
				}
			}
	
			for ($i = 0; $i < count($sub2); $i++) {
				for ($j = 0; $j < count($diffArray); $j++) {
					if ($sub2[$i] == $diffArray[$j]) {
						array_splice($diffArray,$j,1);
					}
				}
			}
	
			for ($i = 0; $i < count($sub3); $i++) {
				for ($j = 0; $j < count($diffArray); $j++) {
					if ($sub3[$i] == $diffArray[$j]) {
						array_splice($diffArray,$j,1);
					}
				}
			}
			return $diffArray;
		}

		function createPuzzle($finalExt, $diff){
			$puzzle = [];
			$emptycells = $diff*10+10;
			$randArray = [];
			$rand = 81;
			$randArray[81]= 0;
			for($i=0; $i<$emptycells;$i++){
				do{$rand = rand(0, 80);}while(in_array($rand, $randArray));
				$randArray[$rand] = $rand;
				array_push($puzzle, $rand);
			}
			for($i=0; $i < count($puzzle); $i++){
				$finalExt[$puzzle[$i]] = "";
	
			}
			return $finalExt;
		}

		function createPdf($cantidad){
			$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
			
			$dataS="";
			$dataP="";
			$imprimir="";

			for ($i = 0; $i < $cantidad ; $i++) {
				if($GLOBALS['simbolos'] == "random"){setSymbol($i % 7);}
				else{setSymbol($GLOBALS['simbolos']);}
				$finalExt = populate();
				$solution=$finalExt;
				if($GLOBALS['diff'] == "progresion"){$puzzle= createPuzzle($finalExt, (($i / 10) % 5 + 1) ); }
				else{$puzzle= createPuzzle($finalExt, $GLOBALS['diff']);}

				$count=1;
				foreach ($solution as $key => $value) {
					if($count == 1){$dataS .= "<tr><td>$value</td>";}
					else{$dataS .= "<td>$value</td>";}
					$count++;
					if($count == 10){$dataS .= "</tr>";$count=1;}
				}
				$puzzleSolved .= "<div class='borde'><table class='table'>$dataS</table></div>";
				if($i < $cantidad - 1){$puzzleSolved .='<pagebreak>';}; 
				$dataS="";

				
				$count=1;
				foreach ($puzzle as $key => $value) {
					if($count == 1){
						$dataP .= "<tr><td>$value</td>";
					}else{$dataP .= "<td>$value</td>";}
					$count++;
					if($count == 10){$dataP .= "</tr>";$count=1;}
				}
				
				$puzzleUnsolve .= "<div class='borde'><table class='table'>$dataP</table></div>";
				$puzzleUnsolve .='<pagebreak>';
				$dataP="";
				
			}
			$imprimir= $puzzleUnsolve . $puzzleSolved;
			$nombreInforme='SymbokuSoultion.pdf';
			$stylesheet = file_get_contents('symboku.css');
			$mpdf->WriteHTML($stylesheet,\Mpdf\HTMLParserMode::HEADER_CSS);
			$mpdf->WriteHTML($imprimir,\Mpdf\HTMLParserMode::HTML_BODY);
			ob_clean(); 
			$mpdf->Output("Symbokus/" .$nombreInforme, "F");
			
		}
		
		createPdf($cantidad);

	}

	
	
?>
<body >
	<div class="dgrid" style="display:grid">
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<div><label for="">Cantidad</label><input type="number" name="cantidad" value="1"></div>
			<div>
				Difficulty 
				<select name="dificultad">
					
					<option value="1">Rookie</option>
					<option value="2">Easy</option>
					<option value="3">Medium </option>
					<option value="4">Hard</option>
					<option value="5">Master</option>
					<option value="progresion">Progresion</option>
				</select>
			</div>
			<div>
				Symbol
				<select name="simbolo">
					<option value="random">Random</option>
					<option value=0>123456789</option>
					<option value=1>ABCDEFGb#</option>
					<option value=2>DoReMi...</option>
					<option value=3>Braille</option>
					<option value="4">YingYang</option>
					<option value="5">RomanNumber</option>
					<option value="6">AsianNumber</option>
					<option value="7"></option>
					<option value="8">Silences</option>
					<option value="">Rythms</option>
					<option value="">Currencies</option>
					<option value="">Pandemic</option>
					<option value="">Chess</option>
					<option value="">Greek</option>
					<option value="">Units</option>
				</select>
			</div>
			<div><input type="submit" name="generar"></div>
		</form>
	</div>

</body>
