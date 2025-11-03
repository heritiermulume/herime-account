<?php
// Serveur de test simple
$port = 8080;
$host = '127.0.0.1';

echo "Démarrage du serveur de test sur http://$host:$port\n";
echo "Appuyez sur Ctrl+C pour arrêter\n\n";

// Fonction pour servir les fichiers
function serveFile($file) {
    if (file_exists($file)) {
        $mimeType = mime_content_type($file);
        header("Content-Type: $mimeType");
        readfile($file);
    } else {
        http_response_code(404);
        echo "Fichier non trouvé: $file";
    }
}

// Créer un serveur simple
$socket = stream_socket_server("tcp://$host:$port", $errno, $errstr);
if (!$socket) {
    die("Erreur: $errstr ($errno)\n");
}

echo "Serveur démarré sur http://$host:$port\n";

while ($conn = stream_socket_accept($socket)) {
    $request = fread($conn, 1024);
    
    // Parser la requête
    $lines = explode("\n", $request);
    $requestLine = $lines[0];
    $parts = explode(' ', $requestLine);
    $method = $parts[0];
    $path = $parts[1];
    
    // Headers de réponse
    $response = "HTTP/1.1 200 OK\r\n";
    $response .= "Content-Type: text/html; charset=utf-8\r\n";
    $response .= "Connection: close\r\n\r\n";
    
    // Contenu de la page
    $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Herime - Serveur Simple</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin: 10px 0; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎉 Serveur de test Herime fonctionne !</h1>
        
        <div class="success">
            ✅ Le serveur PHP fonctionne correctement
        </div>
        
        <div class="info">
            <strong>Informations du serveur :</strong><br>
            Port: ' . $port . '<br>
            Host: ' . $host . '<br>
            Méthode: ' . $method . '<br>
            Chemin: ' . $path . '<br>
            Heure: ' . date('Y-m-d H:i:s') . '
        </div>
        
        <h3>Tests à effectuer :</h3>
        <button onclick="testBackend()">Test Backend Laravel</button>
        <button onclick="testFrontend()">Test Frontend Vue</button>
        
        <div id="results"></div>
        
        <h3>URLs à tester :</h3>
        <ul>
            <li><a href="http://127.0.0.1:8000" target="_blank">Backend Laravel: http://127.0.0.1:8000</a></li>
            <li><a href="http://localhost:5173" target="_blank">Frontend Vue: http://localhost:5173</a></li>
            <li><a href="http://localhost:5174" target="_blank">Frontend Vue (alt): http://localhost:5174</a></li>
        </ul>
    </div>

    <script>
        async function testBackend() {
            try {
                const response = await fetch("http://127.0.0.1:8000/api/health");
                const data = await response.json();
                showResult("Backend Laravel", "success", `✅ Backend fonctionne: ${data.status}`);
            } catch (error) {
                showResult("Backend Laravel", "error", `❌ Erreur backend: ${error.message}`);
            }
        }
        
        async function testFrontend() {
            try {
                const response = await fetch("http://localhost:5173");
                if (response.ok) {
                    showResult("Frontend Vue", "success", "✅ Frontend accessible");
                } else {
                    showResult("Frontend Vue", "error", `❌ Frontend erreur: ${response.status}`);
                }
            } catch (error) {
                showResult("Frontend Vue", "error", `❌ Frontend inaccessible: ${error.message}`);
            }
        }
        
        function showResult(test, type, message) {
            const results = document.getElementById("results");
            const div = document.createElement("div");
            div.className = `success ${type}`;
            div.innerHTML = `<strong>${test}:</strong> ${message}`;
            results.appendChild(div);
        }
    </script>
</body>
</html>';
    
    $response .= $html;
    
    fwrite($conn, $response);
    fclose($conn);
}

fclose($socket);
?>

