<?php /** @var $l \OCP\IL10N */ ?>
<?php
script('core', 'dist/login');
?>

<div id="login"></div>

<?php if (!empty($_['alt_login'])) { ?>
    <div id="alternative-logins">
        <?php foreach($_['alt_login'] as $login): ?>
            <a class="button <?php p(isset($login['style']) ? $login['style'] : ''); ?>" href="<?php print_unescaped($login['href']); ?>" >
                <?php echo $login['name']; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php } ?>
