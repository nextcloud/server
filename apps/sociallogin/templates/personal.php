<?php
/** @var array $_ */
/** @var \OCP\IL10N $l */
?>
<div class="section sociallogin-connect">
    <form id="sociallogin_personal_settings" action="<?php print_unescaped($_['action_url']) ?>" method="POST">
        <input id="disable_password_confirmation" type="checkbox" class="checkbox" name="disable_password_confirmation" value="1" <?php p($_['disable_password_confirmation'] ? 'checked' : '') ?>/>
        <label for="disable_password_confirmation"><?php p($l->t('Disable password confirmation on settings change')) ?></label>
    </form>
    <br/>
    <?php if ($_['allow_login_connect']): ?>
    <h2><?php p($l->t('Social login connect')); ?></h2>
    <ul class="disconnect-logins">
        <?php foreach ($_['connected_logins'] as $title => $url): ?>
        <li><a href="<?php print_unescaped($url) ?>"><?php p($title) ?></a></li>
        <?php endforeach ?>
    </ul>
    <h3><?php p($l->t('Available providers')) ?></h3>
    <main>
        <ul id="alternative-logins">
            <?php foreach ($_['providers'] as $title => $data): ?>
            <li>
                <a class="button primary <?php p(isset($data['style']) ? $data['style'] : '') ?>" href="<?php print_unescaped($data['url']) ?>">
                    <?php p($title) ?>
                </a>
            </li>
            <?php endforeach ?>
        </ul>
    </main>
    <?php endif ?>
</div>
