<?php
if (isset($_SESSION['message'])) {
    echo '<div style="color: forestgreen">'
        . htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8')
        . '</div><br>';
    unset($_SESSION['message']);
}
