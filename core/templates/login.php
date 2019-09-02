<?php /** @var $l \OCP\IL10N */ ?>
<?php
script('core', 'dist/login');
?>


<div id="login"></div>

<?php if (!empty($_['alt_login'])) { ?>
    <div id="alternative-logins">
        <?php foreach($_['alt_login'] as $login): ?>
            <a class="button primary" href="<?php print_unescaped($login['href']); ?>" >
                <?php p($l->t('Log in with')) ?>
                <?php p($login['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php } ?>
