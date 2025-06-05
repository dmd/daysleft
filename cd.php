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
        <div class="center-date"><?php echo date('D M j'); ?></div>
    </div>
</body>
</html>