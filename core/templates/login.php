<?php /** @var $l \OCP\IL10N */ ?>
<?php
script('core', 'dist/login');
?>

<div id="login"></div>

<?php if (!empty($_['alt_login'])) { ?>
    <div id="alternative-logins">
        <?php foreach($_['alt_login'] as $login): ?>
            <a class="button <?php p($login['style'] ?? ''); ?>" aria-label="<?php p($login['label'] ?? $login['name']); ?>" href="<?php print_unescaped($login['href']); ?>" >
                <?php p($login['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php } ?>
