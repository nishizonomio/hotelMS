<?php
function showPopupMessage($message, $type = 'success') {
    $class = ($type === 'success') ? 'popup-success' : 'popup-error';
    echo "
    <div id='popupNotification' class='popup-notification $class'>
        $message
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const popup = document.getElementById('popupNotification');
            // Show popup
            setTimeout(() => {
                popup.classList.add('popup-show');
            }, 100);
            
            // Hide popup after 3 seconds
            setTimeout(() => {
                popup.classList.remove('popup-show');
                // Remove the element after fade out
                setTimeout(() => {
                    popup.remove();
                }, 300);
            }, 3000);
        });
    </script>";
}
?>
