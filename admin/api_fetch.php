<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$tmdb_id = isset($_GET['id']) ? trim($_GET['id']) : '';
$type = $_GET['type'] ?? 'movie'; // 'movie', 'tv', or 'anime'

if (empty($query) && empty($tmdb_id)) {
    echo json_encode(['error' => 'No query or ID provided']);
    exit();
}

$tmdb_key = get_setting('tmdb_api_key', TMDB_API_KEY);
$search_type = ($type === 'tv') ? 'tv' : 'movie';

// Handle Anime (Anilist)
if ($type === 'anime') {
    // Case 1: Fetch by ID
    if (!empty($tmdb_id)) {
        $query_gql = '
        query ($id: Int) {
          Media(id: $id, type: ANIME) {
            id
            title { romaji english native }
            description
            genres
            averageScore
            coverImage { extraLarge }
            bannerImage
            trailer { id site }
          }
        }';
        
        $res = fetch_anilist_data($query_gql, ['id' => (int)$tmdb_id]);
        header('Content-Type: application/json');
        
        if (isset($res['errors'])) {
            echo json_encode(['error' => $res['errors'][0]['message']]);
        } else {
            echo json_encode($res['data']['Media'] ?? ['error' => 'Not found']);
        }
        exit();
    }

    // Case 2: Search
    if (!empty($query)) {
        $gql = '
        query ($search: String) {
          Page (perPage: 15) {
            media (search: $search, type: ANIME) {
              id
              title { romaji english native }
              coverImage { extraLarge }
              bannerImage
            }
          }
        }';
        
        $res = fetch_anilist_data($gql, ['search' => $query]);
        $results = [];
        if (isset($res['data']['Page']['media'])) {
            foreach ($res['data']['Page']['media'] as $media) {
                $results[] = [
                    'id' => $media['id'],
                    'title' => $media['title'],
                    'coverImage' => $media['coverImage'],
                    'bannerImage' => $media['bannerImage']
                ];
            }
        }
        header('Content-Type: application/json');
        if (isset($res['errors'])) {
            echo json_encode(['error' => $res['errors'][0]['message'], 'results' => []]);
        } else {
            echo json_encode(['results' => $results]);
        }
        exit();
    }
}

// Handle Movies/TV (TMDB)
if ($tmdb_key === 'YOUR_TMDB_API_KEY_HERE' || empty($tmdb_key)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'TMDB API Key not configured. Please set it in Admin Settings.']);
    exit();
}

// Case 1: Fetch details by ID
if (!empty($tmdb_id)) {
    $details_url = "https://api.themoviedb.org/3/$search_type/$tmdb_id?api_key=" . $tmdb_key . "&append_to_response=videos,credits";
    $details = fetch_from_api($details_url);
    header('Content-Type: application/json');
    echo json_encode($details);
    exit();
}

// Case 2: Search by query (or numeric ID in search box)
if (!empty($query)) {
    if (is_numeric($query)) {
        $details_url = "https://api.themoviedb.org/3/$search_type/$query?api_key=" . $tmdb_key . "&append_to_response=videos,credits";
        $details = fetch_from_api($details_url);
        header('Content-Type: application/json');
        if (isset($details['id'])) {
            echo json_encode($details);
        } else {
            echo json_encode(['error' => 'ID not found on TMDB']);
        }
        exit();
    } else {
        $search_url = "https://api.themoviedb.org/3/search/$search_type?api_key=" . $tmdb_key . "&query=" . urlencode($query);
        $search_results = fetch_from_api($search_url);
        header('Content-Type: application/json');
        echo json_encode(['results' => $search_results['results'] ?? []]);
        exit();
    }
}

header('Content-Type: application/json');
echo json_encode(['error' => 'No results found']);
?>
