<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<title>Liste des mesures SigFox</title>
	<style>
		body {
			font-family: Futura, Helvetica, Arial, sans-serif;
			display: flex;
			flex-direction: column;
			align-items: center;
		}

		table {
			border-collapse: collapse;
			width: 80%;
		}

		tr:nth-child(odd) {
			background-color: lightgray;
		}

		th,
		td {
			border: 1px solid darkgray;
			padding: .5em;
			text-align: center;
		}
	</style>
</head>

<body>
	<h1>Liste des mesures</h1>
	<table>
		<tr>
			<th>N°</th>
			<th>Température</th>
			<th>Humidité</th>
			<th>Date de mesure</th>
		</tr>
		<? foreach ($data as $id => $measure): ?>
			<tr>
				<td>
					<?= $id ?>
				</td>
				<td>
					<?= $measure["temperature"] ?>°C
				</td>
				<td>
					<?= $measure["humidity"] ?>%
				</td>
				<td>
					<?
					$date = date_create($measure["measure_time"]);
					echo date_format($date, "d.m.Y @ H:i:s");
					?>
				</td>
			</tr>
		<? endforeach; ?>
	</table>
</body>

</html>
