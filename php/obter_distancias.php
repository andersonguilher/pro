<?php
declare(strict_types=1);

// ===== Config de erro: nunca imprimir HTML no response =====
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// Converte avisos/notice em Exception (capturados no try/catch)
set_error_handler(function($severity, $message, $file, $line) {
  if (!(error_reporting() & $severity)) return false; // respeita @
  throw new ErrorException($message, 0, $severity, $file, $line);
});

header('Content-Type: application/json; charset=utf-8');

try {
  require_once __DIR__ . "/conexao.php"; // NÃO deve imprimir nada

  // ---- Chave Geocoding por ambiente ----
  $apiKey = getenv('GOOGLE_MAPS_API_KEY') ?: '';
  if ($apiKey === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Geocoding API key not configured']);
    exit;
  }

  /**
   * Haversine – distância em km
   */
  function calcularDistancia(float $lat1, float $lon1, float $lat2, float $lon2): float {
      $raioTerra = 6371.0;
      $dLat = deg2rad($lat2 - $lat1);
      $dLon = deg2rad($lon2 - $lon1);
      $a = sin($dLat / 2) ** 2 +
           cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
           sin($dLon / 2) ** 2;
      $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
      return $raioTerra * $c;
  }

  /**
   * Ray casting – ponto dentro do polígono.
   * GeoJSON padrão: cada ponto é [lng, lat]. Aqui usamos x=lng, y=lat.
   */
  function pontoDentro(float $lat, float $lng, array $poligono): bool {
      $dentro = false;
      $n = count($poligono);
      for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
          // polígono no padrão [lng, lat] (GeoJSON)
          $xi = (float)$poligono[$i][1]; $yi = (float)$poligono[$i][0];
          $xj = (float)$poligono[$j][1]; $yj = (float)$poligono[$j][0];

          $den = ($yj - $yi);
          if ($den == 0.0) $den = 0.0000001;

          $intersect = (($yi > $lng) != ($yj > $lng)) &&
                       ($lat < ($xj - $xi) * ($lng - $yi) / $den + $xi);
          if ($intersect) $dentro = !$dentro;
      }
      return $dentro;
  }

  /**
   * Geocoding com normalização, timeout e log.
   */
  function geocodeEndereco(string $logradouro, $numero, string $bairro, string $apiKey): array {
      // Normalizações pontuais conhecidas
      $logradouroTratado = str_replace('Vto. de Paciência', 'Viaduto de Paciência', $logradouro);

      $temNumero = ($numero !== null && $numero !== '' && (int)$numero !== 0);
      $numeroParte = $temNumero ? (intval($numero) . ", ") : "";

      $endereco = urlencode("{$logradouroTratado}, {$numeroParte}{$bairro}, Rio de Janeiro - RJ, Brasil");
      $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$endereco}&region=br&key={$apiKey}";

      // Timeout e SSL
      $ctx = stream_context_create([
          'http' => ['timeout' => 6],
          'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true]
      ]);

      $resposta = @file_get_contents($url, false, $ctx);
      if ($resposta === false) {
          error_log("Geocoding HTTP fail: ".preg_replace('/key=[^&]+/', 'key=***', $url));
          return [null, null];
      }

      $json = json_decode($resposta, true);
      $lat = $lng = null;
      $status = "erro";

      if (isset($json['results'][0]['geometry']['location'])) {
          $location = $json['results'][0]['geometry']['location'];
          $lat = isset($location['lat']) ? (float)$location['lat'] : null;
          $lng = isset($location['lng']) ? (float)$location['lng'] : null;
          if ($lat !== null && $lng !== null) $status = "ok";
      }

      // Log seguro
      $logDir = __DIR__ . "/../logs";
      if (!is_dir($logDir)) @mkdir($logDir, 0775, true);

      $linha = "[" . date("Y-m-d H:i:s") . "] {$logradouro}, {$numero} - {$bairro} - {$status}";
      if ($lat !== null && $lng !== null) $linha .= " ({$lat}, {$lng})";
      $linha .= PHP_EOL;
      @file_put_contents($logDir . "/geocode.log", $linha, FILE_APPEND);

      return [$lat, $lng];
  }

  /** @var mysqli $conn */
  global $conn;

  // --------- Endpoint 1: geocode único ----------
  if (isset($_GET['logradouro'], $_GET['bairro'])) {
      $logradouro = trim((string)$_GET['logradouro']);
      $bairro     = trim((string)$_GET['bairro']);
      $numero     = isset($_GET['numero']) && $_GET['numero'] !== '' ? intval($_GET['numero']) : null;

      $lat = $lng = null;

      // Ruas onde o número melhora muito a precisão
      $ruasComNumero = [
          "R. Felipe Cardoso",
          "Av. João XXIII",
          "Av. Areia Branca",
          "Av. Cesário de Melo",
          "Av. Antares",
          "Etr. Urucânia",
          "Etr. da Paciência",
          "Etr. de Santa Eugênia",
          "R. do Império",
          "Av. Isabel",
          "Etr. Cruz das Almas",
          "R. Fernanda",
          "Etr. Vitor Dumas",
          "Etr. Aterrado do Leme",
          "R. Cilon Cunha Brum",
          "Etr. Jose Cid Fernandes",
          "Etr. dos Palmares",
          "Etr. do Cortume"
      ];
      $usarNumero = in_array($logradouro, $ruasComNumero, true) && $numero !== null;

      // Tenta cache em geolocalizacao
      if ($usarNumero) {
          $stmt = $conn->prepare("SELECT latitude, longitude, status FROM geolocalizacao WHERE logradouro = ? AND numero = ? AND bairro = ? LIMIT 1");
          $stmt->bind_param("sis", $logradouro, $numero, $bairro);
      } else {
          $stmt = $conn->prepare("SELECT latitude, longitude, status FROM geolocalizacao WHERE logradouro = ? AND bairro = ? LIMIT 1");
          $stmt->bind_param("ss", $logradouro, $bairro);
      }
      $stmt->execute();
      $res = $stmt->get_result();

      if ($res && $res->num_rows > 0) {
          $geo = $res->fetch_assoc();
          if (($geo['status'] ?? '') === 'ok') {
              $lat = (float)$geo['latitude'];
              $lng = (float)$geo['longitude'];
          }
      }

      // Cache não encontrado ou sem sucesso: chama API
      if ($lat === null || $lng === null) {
          [$lat, $lng] = geocodeEndereco($logradouro, $numero, $bairro, $apiKey);

          if ($lat !== null && $lng !== null) {
              if ($usarNumero) {
                  $stmtIns = $conn->prepare("INSERT INTO geolocalizacao (logradouro, numero, bairro, latitude, longitude, status) VALUES (?, ?, ?, ?, ?, 'ok')");
                  $stmtIns->bind_param("sisdd", $logradouro, $numero, $bairro, $lat, $lng);
              } else {
                  $stmtIns = $conn->prepare("INSERT INTO geolocalizacao (logradouro, bairro, latitude, longitude, status) VALUES (?, ?, ?, ?, 'ok')");
                  $stmtIns->bind_param("ssdd", $logradouro, $bairro, $lat, $lng);
              }
              $stmtIns->execute();
          } else {
              if ($usarNumero) {
                  $stmtErro = $conn->prepare("INSERT INTO geolocalizacao (logradouro, numero, bairro, latitude, longitude, status) VALUES (?, ?, ?, NULL, NULL, 'erro')");
                  $stmtErro->bind_param("sis", $logradouro, $numero, $bairro);
              } else {
                  $stmtErro = $conn->prepare("INSERT INTO geolocalizacao (logradouro, bairro, latitude, longitude, status) VALUES (?, ?, NULL, NULL, 'erro')");
                  $stmtErro->bind_param("ss", $logradouro, $bairro);
              }
              $stmtErro->execute();
          }
      }

      echo json_encode([
          "latitude"  => $lat,
          "longitude" => $lng
      ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      exit;
  }

  // --------- Endpoint 2: lista de chamados ----------
  // Carrega polígonos do GeoJSON (se existir)
  $poligonos = [];
  $geojsonPath = __DIR__ . "/../geodata.geojson";
  if (is_readable($geojsonPath)) {
      $geojsonRaw = file_get_contents($geojsonPath);
      $geojson = json_decode($geojsonRaw, true);
      if (is_array($geojson) && isset($geojson["features"]) && is_array($geojson["features"])) {
          foreach ($geojson["features"] as $feature) {
              if (($feature["geometry"]["type"] ?? '') === "Polygon") {
                  // GeoJSON: Polygon -> coordinates[0] é o anel externo
                  $poligonos[] = $feature["geometry"]["coordinates"][0];
              }
          }
      }
  }

  $latUser = isset($_GET['lat']) ? (float)$_GET['lat'] : 0.0;
  $lngUser = isset($_GET['lng']) ? (float)$_GET['lng'] : 0.0;

  $sql = "SELECT id_chamado, data_sla, subtipo, logradouro, numero, bairro, complemento, referencia, ds_chamado, geoloc
          FROM dados1746
          WHERE status IN ('Aberto', 'Em andamento') AND setor LIKE '%Fiscal%'";
  $result = $conn->query($sql);

  $chamados = [];
  $ruasComNumero = [
      "R. Felipe Cardoso",
      "Av. João XXIII",
      "Av. Areia Branca",
      "Av. Cesário de Melo",
      "Av. Antares",
      "Etr. Urucânia",
      "Etr. da Paciência",
      "Etr. de Santa Eugênia",
      "R. do Império",
      "Av. Isabel",
      "Etr. Cruz das Almas",
      "R. Fernanda",
      "Etr. Vitor Dumas",
      "Etr. Aterrado do Leme",
      "R. Cilon Cunha Brum",
      "Etr. Jose Cid Fernandes",
      "Etr. dos Palmares",
      "Etr. do Cortume"
  ];

  while ($row = $result->fetch_assoc()) {
      $rowLogradouro = (string)$row["logradouro"];
      $rowBairro     = (string)$row["bairro"];
      $rowNumero     = ($row["numero"] !== null && $row["numero"] !== '') ? (int)$row["numero"] : null;

      $endereco = $rowLogradouro . ", " . ($rowNumero ?? '') . " - " . $rowBairro;
      $lat = $lng = null;

      // Usa geoloc bruto se vier preenchido como "lat,lng"
      if (!empty($row["geoloc"]) && strpos($row["geoloc"], ",") !== false) {
          [$lat, $lng] = array_map('floatval', explode(",", $row["geoloc"], 2));
      } else {
          $usarNumero = in_array($rowLogradouro, $ruasComNumero, true) && ($rowNumero !== null);

          // tenta cache em geolocalizacao
          if ($usarNumero) {
              $stmt = $conn->prepare("SELECT latitude, longitude, status FROM geolocalizacao WHERE logradouro = ? AND numero = ? AND bairro = ? LIMIT 1");
              $stmt->bind_param("sis", $rowLogradouro, $rowNumero, $rowBairro);
          } else {
              $stmt = $conn->prepare("SELECT latitude, longitude, status FROM geolocalizacao WHERE logradouro = ? AND bairro = ? LIMIT 1");
              $stmt->bind_param("ss", $rowLogradouro, $rowBairro);
          }
          $stmt->execute();
          $resGeo = $stmt->get_result();

          if ($resGeo && $resGeo->num_rows > 0) {
              $geo = $resGeo->fetch_assoc();
              if (($geo["status"] ?? '') === "ok") {
                  $lat = (float)$geo["latitude"];
                  $lng = (float)$geo["longitude"];
              }
          }

          // sem cache ou cache com erro -> chama API
          if ($lat === null || $lng === null) {
              [$lat, $lng] = geocodeEndereco($rowLogradouro, $rowNumero, $rowBairro, $apiKey);

              if ($lat !== null && $lng !== null) {
                  if ($usarNumero) {
                      $stmtIns = $conn->prepare("INSERT INTO geolocalizacao (logradouro, numero, bairro, latitude, longitude, status) VALUES (?, ?, ?, ?, ?, 'ok')");
                      $stmtIns->bind_param("sisdd", $rowLogradouro, $rowNumero, $rowBairro, $lat, $lng);
                  } else {
                      $stmtIns = $conn->prepare("INSERT INTO geolocalizacao (logradouro, bairro, latitude, longitude, status) VALUES (?, ?, ?, ?, 'ok')");
                      $stmtIns->bind_param("ssdd", $rowLogradouro, $rowBairro, $lat, $lng);
                  }
                  $stmtIns->execute();
              } else {
                  if ($usarNumero) {
                      $stmtErro = $conn->prepare("INSERT INTO geolocalizacao (logradouro, numero, bairro, latitude, longitude, status) VALUES (?, ?, ?, NULL, NULL, 'erro')");
                      $stmtErro->bind_param("sis", $rowLogradouro, $rowNumero, $rowBairro);
                  } else {
                      $stmtErro = $conn->prepare("INSERT INTO geolocalizacao (logradouro, bairro, latitude, longitude, status) VALUES (?, ?, NULL, NULL, 'erro')");
                      $stmtErro->bind_param("ss", $rowLogradouro, $rowBairro);
                  }
                  $stmtErro->execute();
              }
          }
      }

      // Dentro/fora da área (default = fora se não achou polígonos)
      $dentro = false;
      if ($lat !== null && $lng !== null && !empty($poligonos)) {
          foreach ($poligonos as $poli) {
              if (pontoDentro((float)$lat, (float)$lng, $poli)) {
                  $dentro = true;
                  break;
              }
          }
      }

      $row["distancia"] = ($lat !== null && $lng !== null && $latUser !== 0.0 && $lngUser !== 0.0)
          ? calcularDistancia($latUser, $lngUser, (float)$lat, (float)$lng)
          : 9999.0;

      $row["endereco"] = $endereco;
      $row["latitude_geolocalizacao"]  = $lat;
      $row["longitude_geolocalizacao"] = $lng;
      $row["fora_area"] = !$dentro;

      $chamados[] = $row;
  }

  usort($chamados, fn($a, $b) => $a['distancia'] <=> $b['distancia']);

  echo json_encode($chamados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  // Nunca vazar HTML; loga e devolve JSON
  error_log('[obter_distancias] '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
  http_response_code(500);
  echo json_encode(['error' => 'Internal error while fetching data.']);
}
