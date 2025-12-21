<?php ob_start(); ?>

<div class="page-header">
    <h1>Terms and Conditions</h1>
</div>

<div class="card">
    <div class="card-body legal-content">
        <p class="text-muted mb-4">Last updated: <?= date('F j, Y') ?></p>

        <h2>1. Acceptance of Terms</h2>
        <p>By accessing and using Razor Library ("the Service"), you accept and agree to be bound by these Terms and Conditions. If you do not agree to these terms, please do not use the Service.</p>

        <h2>2. Description of Service</h2>
        <p>Razor Library is a personal collection management tool for wet shaving enthusiasts. The Service allows users to catalog safety razors, blades, brushes, and other shaving accessories with photos and usage tracking.</p>

        <h2>3. Account Registration and Access</h2>
        <h3>3.1 Account Creation</h3>
        <p>Access to Razor Library requires an approved account. Account requests are reviewed by administrators and approval is at their sole discretion.</p>

        <h3>3.2 Free Trial</h3>
        <p>New approved accounts receive a 7-day free trial with full access to all features. After the trial period, a subscription is required to continue using the Service.</p>

        <h3>3.3 Subscription</h3>
        <p>Subscriptions are managed through Buy Me a Coffee. Subscription terms, pricing, and renewal policies are as specified on the Buy Me a Coffee platform.</p>

        <h3>3.4 Account Responsibility</h3>
        <p>You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>

        <h2>4. User Content</h2>
        <h3>4.1 Ownership</h3>
        <p>You retain full ownership of all images and content you upload to Razor Library. We do not claim any ownership rights to your content.</p>

        <h3>4.2 Content Guidelines</h3>
        <p>All uploaded images should be related to wet shaving equipment and accessories. The following types of content are prohibited:</p>
        <ul>
            <li>Content unrelated to wet shaving</li>
            <li>Illegal, offensive, or inappropriate content</li>
            <li>Content that infringes on intellectual property rights</li>
            <li>Malicious files or code</li>
        </ul>

        <h3>4.3 Content Removal</h3>
        <p>We reserve the right to remove any content that violates these guidelines or is deemed inappropriate at our sole discretion.</p>

        <h2>5. Privacy and Data</h2>
        <h3>5.1 Data Collection</h3>
        <p>We collect only the information necessary to provide the Service, including:</p>
        <ul>
            <li>Account information (username, email)</li>
            <li>Collection data (items, images, usage records)</li>
            <li>Activity logs for security purposes</li>
        </ul>

        <h3>5.2 Data Usage</h3>
        <p>Your data is used solely to provide and improve the Service. We do not sell or share your personal information with third parties, except as required by law.</p>

        <h3>5.3 Data Export</h3>
        <p>You may export your complete collection data at any time through your profile settings.</p>

        <h3>5.4 Data Deletion</h3>
        <p>You may request deletion of your account and all associated data at any time. Deleted data cannot be recovered after the 30-day recovery period.</p>

        <h2>6. Service Availability</h2>
        <p>While we strive to maintain continuous availability of the Service, we do not guarantee uninterrupted access. We may perform maintenance, updates, or experience outages that affect availability.</p>

        <h2>7. Modifications to Terms</h2>
        <p>We reserve the right to modify these Terms and Conditions at any time. Users will be notified of significant changes via email or in-app notification. Continued use of the Service after changes constitutes acceptance of the modified terms.</p>

        <h2>8. Termination</h2>
        <p>We reserve the right to terminate or suspend access to the Service at our discretion, including but not limited to:</p>
        <ul>
            <li>Violation of these Terms and Conditions</li>
            <li>Non-payment of subscription fees</li>
            <li>Abuse of the Service</li>
        </ul>

        <h2>9. Limitation of Liability</h2>
        <p>The Service is provided "as is" without warranties of any kind. We are not liable for any direct, indirect, incidental, or consequential damages arising from use of the Service.</p>

        <h2>10. Contact</h2>
        <p>For questions about these Terms and Conditions, please contact the site administrator.</p>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/src/Views/layouts/app.php';
?>
