<!-- Floating Help Button -->
<a href="manual" target="_blank" class="help-button" title="User Manual">
    <span class="help-icon">?</span>
</a>

<!-- Styles for the Help Button -->
<style>
    /* Floating button styling */
    .help-button {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background-color: #4a90e2;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        font-weight: bold;
        text-decoration: none;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        transition: transform 0.3s ease;
        cursor: pointer;
    }

    /* Hover effect */
    .help-button:hover {
        transform: scale(1.1);
        background-color: #357ABD;
    }

    /* Animation for pulsing effect */
    .help-button::after {
        content: "";
        position: absolute;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: rgba(74, 144, 226, 0.5);
        animation: pulse 2s infinite;
        z-index: -1;
    }

    /* Pulse animation */
    @keyframes pulse {
        0% {
            transform: scale(1);
            opacity: 0.7;
        }
        100% {
            transform: scale(1.5);
            opacity: 0;
        }
    }

    /* Styling the question mark icon */
    .help-icon {
        font-family: Arial, sans-serif;
    }
</style>
