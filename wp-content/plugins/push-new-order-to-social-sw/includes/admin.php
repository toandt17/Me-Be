<?php
	?>
		<div class="wrap">
		
			<h2 class="title-ponsw"><?php _e(' New order Notification for Facebok & Telegram - SonWeb', 'sonweb');?></h2>
			<div class="donate">
				<a class="button button-large" href="<?php echo esc_url(' https://www.paypal.com/paypalme/sonwebtl/2usd');?>" target="_blank">
					<img class="emoji" src="<?php echo esc_url('https://s.w.org/images/core/emoji/14.0.0/svg/2705.svg'); ?>" />
					<?php _e('Click to Donate - Paypal', 'sonweb');?>
				</a>
			</div><!--end donate-->
			<div id="poststuff">
				<div id="post-body">
					<div id="post-body-content">
						<div class="postbox ponsw-settings">
							<h3 class="hndle"><?php _e('Plugin Settings', 'sonweb');?></h3>
							<div class="inside">
								<div class="notice-setting">
									<p> <?php _e('You need to get a personal APIkey from CallMeBot.com to to Facebook', 'sonweb');?></p>
									<strong><?php _e('Create new APIKey Facebook', 'sonweb');?>  <a href="<?php echo esc_url('https://m.me/api.callmebot');?> " target="_blank"> <?php _e('https://m.me/api.callmebot', 'sonweb');?></a></strong> <br>
									<strong><?php _e('Authorize CallMeBot to Telegram', 'sonweb');?> <a href="<?php echo esc_url('https://api2.callmebot.com/txt/login.php');?>" target="_blank"><?php _e('https://api2.callmebot.com/txt/login.php', 'sonweb');?> </a></strong><br>
									<hr>
									<span><?php _e('Copy key and Username Telegram insert to input', 'sonweb');?></span>
								</div>
								<form method="post" action="options.php" class="cnb-container">
									<?php settings_fields('ponsw_options'); ?>
									<table class="form-table">
										<tr valign="top">
											<th scope="row"><?php _e('Key Facebook:','sonweb');?> </th>
											<td><input type="text" name="ponsw_setting[ponswFacebook]" value="<?php echo esc_attr($this->options_setting['ponswFacebook']);?>" /></td>
										</tr>
										<tr valign="top">
											<th scope="row"><?php _e('UserName telegram:','sonweb');?></th>
											<td><input type="text" name="ponsw_setting[ponswTelegram]" value="<?php echo esc_attr($this->options_setting['ponswTelegram']);?>" /></td>
										</tr>
										<tr valign="top">
											<th scope="row"><?php _e('Send Message:','sonweb');?></th>
											<td>
												<textarea id="ponsw_Message" placeholder="Content new order" name="ponsw_setting[ponswMessage]" rows="10" cols="40">
													<?php 
														$notifyContent =  $this->options_setting['ponswMessage'];
														if ( empty($notifyContent) ) {
															$notifyContent = "Your order ID: #%%order_id%%
																Products name: %%product_name%%
																First name: %%first_name%%
																Last name: %%last_name%%
																Customer email: %%billing_email%%
																Phone number: %%billing_phone%%
																Address: %%billing_address%%
																Total money: %%total%%";
														}
													?>
													<?php echo  esc_textarea($notifyContent);?>
												</textarea>
											</td>
											<td>
												<div class="desc_example">
												<h3><?php _e('You can copy and pass Send Message:','sonweb');?></h3>
														Your order ID: <span style="color: blue;">#%%order_id%%</span><br>
														Products name: <span style="color: red;">%%product_name%%</span><br>
														First name: <span style="color: blue;">%%first_name%%</span><br>
														Last name: <span style="color: red;">%%last_name%%</span><br>
														Customer email: <span style="color: blue;">%%billing_email%%</span><br>
														Phone number: <span style="color: red;">%%billing_phone%%</span><br>
														Address: <span style="color: blue;">%%billing_address%%</span><br>
														Total money: <span style="color: blue;">%%total%%</span>
												</div>
											</td>
										</tr>
										<tr valign="top">
											<td>
												<label>
													<input type="checkbox"
														name="ponsw_setting[ckFace]"
														id="ck_MesFace"
														value="1" <?php checked('1', intval(isset( $this->options_setting['ckFace']) ), true) ; ?>/>
														<?php _e('Notify to Facebook','sonweb');?>
												</label>
											</td>
											<td>
												<label>
													<input type="checkbox"
														name="ponsw_setting[ckTel]"
														id="ck_Tel"
														value="1" <?php checked('1', intval(isset( $this->options_setting['ckTel']) ), true); ?>/>
														<?php _e('Notify to Telegram','sonweb');?>
												</label>
											</td>
										</tr>
									</table>
									
								<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
								</form>
							</div><!--end inside-->
						</div><!--end ponsw-->
						
					</div><!--end post-body-content-->
				</div>
			</div>
		</div>
	<?php
?>