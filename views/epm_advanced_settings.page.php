<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>

<?php foreach($config['data'] ?? [] as $keySec => $section): ?>
	<div class="section-title" data-for="<?= $keySec ?>">
		<h3><i class="fa fa-minus"></i><?= $section['label'] ?></h3>
	</div>
	<div class="section" data-id="<?= $keySec ?>">
		<?php foreach($section['items'] as $keyItem => $item): ?>

			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">

								<?php switch($item['type'] ?? ''):
									case 'text': ?>
										<div class="col-md-3">
											<label class="control-label" for="<?= $keyItem ?>"><?= $item['label'] ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="<?= $keyItem ?>"></i>
										</div>
										<div class="col-md-9">
											<?php if(! empty($item['button']) && is_array($item['button'])): ?>

												<div class="input-group">
													<input type="text" class="form-control <?= $item['class'] ?? ''?>" placeholder="<?= $item['placeholder'] ?? _("Input Text...") ?>" id="<?= $keyItem ?>" name="<?= $keyItem ?>" value="<?= $item['value'] ?>">
													<span class="input-group-append">
														<button class="btn btn-default <?= $item['button']['class'] ?? ''?>" type="button" id='<?= $item['button']['id'] ?? ''?>' onclick="<?= $item['button']['onclick'] ?? ''?>"><i class='fa <?= $item['button']['icon'] ?? ''?>'></i><?= $item['button']['label'] ?? '??'?></button>
													</span>
												</div>

											<?php else: ?>
												<input type="text" class="form-control <?= $item['class'] ?? ''?>" placeholder="<?= $item['placeholder'] ?? _("Input Text...") ?>" id="<?= $keyItem ?>" name="<?= $keyItem ?>" value="<?= $item['value'] ?>">
											<?php endif; ?>
										</div>
										<?php break;

									case 'select': ?>
										<div class="col-md-3">
											<label class="control-label" for="<?= $keyItem ?>"><?= $item['label'] ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="<?= $keyItem ?>"></i>
										</div>
										<div class="col-md-9">
											<?php if(! empty($item['button']) && is_array($item['button'])): ?>
												<div class="input-group input-group-br">
													<select class="form-control selectpicker show-tick <?= $item['class'] ?? ''?>" data-style="btn-info" data-size="<?= $item['size'] ?? 10 ?>" data-live-search="<?= $item['search'] ?? false ?>" data-live-search-placeholder="<?= $item['search_placeholder'] ?? _("Serach") ?>" name="<?= $keyItem ?>" id="<?= $keyItem ?>">
														<?php foreach($item['options'] as $keyOption => $option): ?>
															<option data-icon="fa <?= $option['icon'] ?? $item['icon_option'] ?? '' ?>" value="<?= $option['value'] ?>" <?= ($item['select_check']($option, $item['value']) ? 'selected="selected"' : '') ?> ><?= $option['text'] ?></option>
														<?php endforeach; ?>
													</select>
													<span class="input-group-append">
														<button class="btn btn-default <?= $item['button']['class'] ?? ''?>" type="button" id='<?= $item['button']['id'] ?? ''?>' onclick="<?= $item['button']['onclick'] ?? ''?>"><i class='fa <?= $item['button']['icon'] ?? ''?>'></i><?= $item['button']['label'] ?? '??'?></button>
													</span>
												</div>
											<?php else: ?>
												<select class="form-control selectpicker show-tick <?= $item['class'] ?? ''?>" data-style="btn-info" name="<?= $keyItem ?>" id="<?= $keyItem ?>">
													<?php foreach($item['options'] as $keyOption => $option): ?>
														<option data-icon="fa <?= $option['icon'] ?? $item['icon_option'] ?? '' ?>" value="<?= $keyOption ?>" <?= ( $item['select_check']($option, $item['value']) ? 'selected="selected"' : '') ?> ><?= $option['text'] ?></option>
													<?php endforeach; ?>
												</select>
											<?php endif; ?>

											<?php if(!empty($item['alert']) && is_array($item['alert'])): ?>
												<?php foreach($item['alert'] as $keyAlter => $alert): ?>
													<?php if(empty($alert['types']) || empty($alert['msg'])) continue; ?>
													<?php if(! in_array($item['value'], $alert['types'])) continue; ?>
													<br><br>
													<div class="alert alert-info" role="alert" id="<?= $keyAlter ?>">
														<?= $alert['msg'] ?>
													</div>
												<?php endforeach; ?>										
											<?php endif; ?>
										</div>
										<?php break;

									case 'radioset': ?>
										<div class="col-md-12">
											<label class="control-label" for="<?= $keyItem ?>" <?= ($item['disabled'] ?? false) ? 'disabled' : '' ?> ><?= $item['label'] ?></label>
											<i class="fa fa-question-circle fpbx-help-icon" data-for="<?= $keyItem ?>"></i>
											<div class="radioset pull-xs-right">
												<?php foreach($item['options'] as $keyOption => $option): ?>
													<input <?= ($option['disabled'] ?? $item['disabled'] ?? false) ? 'disabled' : '' ?> type="radio" name="<?= $keyItem ?>" id="<?= $keyItem ?>_<?= $keyOption ?>" value="<?= $option['value'] ?>" <?= ($option['value'] == $item['value'] ? 'CHECKED' : '') ?> >
													<label <?= ($option['disabled'] ?? $item['disabled'] ?? false) ? 'disabled' : '' ?> for="<?= $keyItem ?>_<?= $keyOption ?>"><i class="fa <?= $option['icon'] ?>"></i> <?= $option['label'] ?></label>
												<?php endforeach; ?>
											</div>
										</div>
										<?php break;


								endswitch; ?>

							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span class="help-block fpbx-help-block" id="<?= $keyItem ?>-help"><?= $item['help'] ?></span>
					</div>
				</div>
			</div>		
		<?php endforeach; ?>

	</div>
<?php endforeach; ?>