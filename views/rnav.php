<?php
	if (!defined('FREEPBX_IS_AUTH'))
	{
		exit(_('No direct script access allowed'));
	}

	// Define el menú como una estructura de datos
	$menuConfig = array(
		'oss_info' => array(
			'title' => _('Open Source Information'),
			'items' => array(
				'epm_oss' => array('icon' => 'fa-info-circle', 'label' => _('About OSS Endpoint Manager'))
			),
			'condition' => function($li) {
				return array_key_exists('epm_oss', $li);
			}
		),
		'endpoint_manager' => array(
			'title' => _('Endpoint Manager'),
			'items' => array(
				'epm_advanced' => array('icon' => 'fa-cog', 'label' => _('Settings')),
				'epm_devices'  => array('icon' => 'fa-list', 'label' => _('Extension Mapping'))
			),
			'condition' => function($li) {
				return array_key_exists('epm_advanced', $li) || array_key_exists('epm_devices', $li);
			}
		),
		'brands' => array(
			'title' => _('Brands'),
			'items' => array(
				'epm_config' => array('icon' => 'fa-folder-open', 'label' => _('Package Manager'))
			),
			'condition' => function($li) {
				return array_key_exists('epm_config', $li);
			}
		),
		'advanced' => array(
			'title' => _('Advanced'),
			'items' => array(
				'epm_templates'    => array('icon' => 'fa-list', 'label' => _('Template Manager')),
				'epm_placeholders' => array('icon' => 'fa-hashtag', 'label' => _('Config File Placeholders'))
			),
			'condition' => function($li) {
				return array_key_exists('epm_templates', $li) || array_key_exists('epm_placeholders', $li);
			}
		)
	
	);
	
	// Filter menu items based on user access
	$li = array();
	foreach ($menuConfig as $k => $v)
	{
		if (! is_array($v['items'])) { continue; }

		foreach ($v['items'] as $subK => $subV)
		{
			if (is_object($_SESSION["AMP_user"]) && !$_SESSION["AMP_user"]->checkSection($subK))
			{
				continue;
			}
			$li[$subK] = $subV;
		}
	}
?>
<br/>
<div id="toolbar-all-side list-group">
	<?php foreach ($menuConfig as $section): ?>
		<?php if ($section['condition']($li)): ?>
			<span class="list-group-item">
				<h3><?= $section['title'] ?></h3>
				<?php foreach ($section['items'] as $name => $item): ?>
					<?php if (array_key_exists($name, $li)): ?>
						<a href="?display=<?= $name ?>" class="btn list-group-item">
							<i class="fa <?= $item['icon'] ?>"></i>&nbsp; <?= $item['label'] ?>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>
			</span>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
<br/>