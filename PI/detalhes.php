<?php
include("config/db.php");

$turma_id = $_GET['turma_id'];

// Pega todas as aulas dessa turma
$sql = "SELECT * FROM aulas WHERE turma_id = $turma_id";
$result = mysqli_query($conn, $sql);

echo "<h2>Aulas da Turma $turma_id</h2>";

if(mysqli_num_rows($result) == 0){
    echo "<p>Nenhuma aula cadastrada.</p>";
} else {
    echo "<ul>";
    while($aula = mysqli_fetch_assoc($result)){
        echo "<li>";
        echo "<strong>" . $aula['disciplina'] . "</strong> - ";
        echo "Data: " . $aula['data'] . " ";
        echo "Hora: " . $aula['hora'] . " ";
        echo "Sala: " . $aula['sala'];
        echo "</li>";
    }
    echo "</ul>";
}
?>
<a href="index.php">◀ Voltar</a>
