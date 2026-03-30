<?php
// Simple IMDb Scraper (Fallback for missing TMDB data)

function scrape_imdb($imdb_id) {
    if (strpos($imdb_id, 'tt') !== 0) {
        $imdb_id = 'tt' . $imdb_id;
    }
    
    $url = "https://www.imdb.com/title/$imdb_id/";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return false;

    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    $data = [];

    // Extract Rating (based on current IMDb structure)
    // Note: IMDb UI changes often, so this is a best-effort approach
    $rating_node = $xpath->query("//span[contains(@class, 'sc-bde20123-1')]");
    if ($rating_node->length > 0) {
        $data['rating'] = $rating_node->item(0)->nodeValue;
    }

    // Extract Cast
    $cast_nodes = $xpath->query("//a[contains(@data-testid, 'title-cast-item__actor')]");
    $cast = [];
    foreach ($cast_nodes as $node) {
        $cast[] = trim($node->nodeValue);
    }
    $data['cast'] = implode(', ', array_slice($cast, 0, 10));

    return $data;
}

// Handler for AJAX
if (isset($_GET['imdb_id']) && is_admin()) {
    $id = $_GET['imdb_id'];
    $scraped = scrape_imdb($id);
    header('Content-Type: application/json');
    echo json_encode($scraped);
    exit();
}
?>
