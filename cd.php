<?php
function getQueryParam($param, $default = null) {
    return isset($_GET[$param]) ? $_GET[$param] : $default;
}

function calculateDaysLeft($targetDate) {
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    $target = new DateTime($targetDate);
    $target->setTime(0, 0, 0);
    
    $diff = $today->diff($target);
    $daysLeft = $diff->days;
    
    if ($target < $today) {
        $daysLeft = -$daysLeft;
    }
    
    return $daysLeft;
}

function formatDate($dateStr) {
    $date = new DateTime($dateStr);
    return $date->format('F j, Y');
}

function getCountdownData($index) {
    $date = getQueryParam("date$index");
    $event = getQueryParam("event$index");
    
    if (!$date || !$event) {
        return [
            'number' => '',
            'text' => '',
            'hasError' => false,
            'isEmpty' => true
        ];
    }
    
    try {
        $daysLeft = calculateDaysLeft($date);
        $formattedDate = formatDate($date);
        
        if ($daysLeft < 0) {
            $text = htmlspecialchars($event) . ' was ' . abs($daysLeft) . ' days ago<br><span class="date-text">' . htmlspecialchars($formattedDate) . '</span>';
        } else if ($daysLeft === 0) {
            $text = htmlspecialchars($event) . ' is today!<br><span class="date-text">' . htmlspecialchars($formattedDate) . '</span>';
        } else {
            $text = htmlspecialchars($event) . '<br><span class="date-text">' . htmlspecialchars($formattedDate) . '</span>';
        }
        
        return [
            'number' => $daysLeft,
            'text' => $text,
            'hasError' => false,
            'isEmpty' => false
        ];
    } catch (Exception $e) {
        return [
            'number' => '!',
            'text' => 'Invalid date format',
            'hasError' => true,
            'isEmpty' => false
        ];
    }
}

$countdowns = [];
for ($i = 1; $i <= 4; $i++) {
    $countdowns[$i] = getCountdownData($i);
}

// Sort countdowns by days left (farthest to soonest: highest number to lowest)
// Filter out empty entries first, then sort the remaining ones
$nonEmptyCountdowns = array_filter($countdowns, function($countdown) {
    return !$countdown['isEmpty'];
});

// Sort by days left (descending: farthest in future first)
uasort($nonEmptyCountdowns, function($a, $b) {
    // Handle error cases - put them at the end
    if ($a['hasError'] && !$b['hasError']) return 1;
    if (!$a['hasError'] && $b['hasError']) return -1;
    if ($a['hasError'] && $b['hasError']) return 0;
    
    // Sort by days left (descending)
    return $b['number'] - $a['number'];
});

// Create sorted array maintaining grid positions (1-4)
$sortedCountdowns = [];
$position = 1;
foreach ($nonEmptyCountdowns as $countdown) {
    $sortedCountdowns[$position] = $countdown;
    $position++;
}

// Fill remaining positions with empty entries
for ($i = $position; $i <= 4; $i++) {
    $sortedCountdowns[$i] = [
        'number' => '',
        'text' => '',
        'hasError' => false,
        'isEmpty' => true
    ];
}

$countdowns = $sortedCountdowns;

// Calculate dots based on days since .lastmarzi file mtime
function getDotCount() {
    $filePath = '/home/edges/3e.org/private/.lastmarzi';
    if (file_exists($filePath)) {
        $mtime = filemtime($filePath);
        if ($mtime !== false) {
            $fileDate = new DateTime();
            $fileDate->setTimestamp($mtime);
            $fileDate->setTime(0, 0, 0); // Reset to start of day
            
            $today = new DateTime();
            $today->setTime(0, 0, 0); // Reset to start of day
            
            $diff = $today->diff($fileDate);
            $daysSince = $diff->days;
            
            return max(0, $daysSince); // Don't return negative values
        }
    }
    return 0; // Return 0 if file doesn't exist or can't get mtime
}

$dotCount = getDotCount();

// Get letterday from API
function getLetterDay() {
    $url = 'https://3e.org/gibbs/letter_day_calculator.php';
    $context = stream_context_create([
        'http' => [
            'timeout' => 5 // 5 second timeout
        ]
    ]);
    
    $json = @file_get_contents($url, false, $context);
    if ($json === false) {
        return ''; // Return empty string if API call fails
    }
    
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['letterday'])) {
        return ''; // Return empty string if JSON is invalid or letterday not found
    }
    
    return $data['letterday'];
}

$letterDay = getLetterDay();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Days Left Countdown</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background-color: white;
            color: black;
            height: 100vh;
            box-sizing: border-box;
        }

        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 20px;
            height: 100%;
            position: relative;
        }

        .countdown-cell {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .countdown-number {
            font-size: 6rem;
            font-weight: 900;
            line-height: 0.8;
            margin: 0;
            padding: 0;
        }

        .countdown-text {
            font-size: 1.44rem;
            font-weight: 700;
            margin-top: 1rem;
            max-width: 90%;
            word-wrap: break-word;
            line-height: 0.8;
        }

        .date-text {
            font-size: 1.08rem;
            font-weight: 600;
            margin-top: 0.5rem;
            display: inline-block;
        }

        .error {
            font-size: 1rem;
            font-weight: 700;
            color: black;
        }

        .empty {
            visibility: hidden;
        }

        .center-date {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2rem;
            font-weight: 700;
            color: black;
            z-index: 10;
            pointer-events: none;
        }

        .center-dots {
            position: absolute;
            top: calc(50% + 2rem);
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.5rem;
            font-weight: 700;
            color: black;
            z-index: 10;
            pointer-events: none;
            letter-spacing: 0.2rem;
        }
    </style>
</head>
<body>
    <div class="grid-container">
        <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="countdown-cell<?php echo $countdowns[$i]['isEmpty'] ? ' empty' : ''; ?>">
                <div class="countdown-number"><?php echo htmlspecialchars($countdowns[$i]['number']); ?></div>
                <div class="countdown-text<?php echo $countdowns[$i]['hasError'] ? ' error' : ''; ?>"><?php echo $countdowns[$i]['text']; ?></div>
            </div>
        <?php endfor; ?>
        <div class="center-date"><?php echo date('D M j') . ($letterDay ? ' (' . htmlspecialchars($letterDay) . ')' : ''); ?></div>
        <div class="center-dots"><?php echo str_repeat('·', $dotCount); ?></div>
    </div>
</body>
</html>