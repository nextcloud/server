<template>
    <div id="security-password" class="section">
        <!-- title and saved status message-->
        <h2 class="inlineblock">{{ t('settings','Password') }}</h2>
        <span id="password-error-msg" class="msg success hidden">Saved</span>

        <!-- password change form -->
        <div class="personal-settings-setting-box personal-settings-password-box">
			<form id="passwordform" @submit.prevent="changePassword">
                <label for="pass1" class="hidden-visually">{{ t('settings','Current password') }}</label>
				<input type="password" id="pass1" name="oldpassword"
                    :placeholder="t('settings','Current password')"
                    :value="oldpass"
                    autocomplete="off" autocapitalize="none" autocorrect="off" />

                <v-popover trigger="manual" :open="isOpen" offset="3" :auto-hide="false">
                    <div class="personal-show-container">
                        <label for="pass2" class="hidden-visually">{{ t('settings','New password') }}</label>
                        <input type="password" id="pass2" name="newpassword"
                            :placeholder="t('settings','New password')"
                            :value="newpass"
                            data-typetoggle="#personal-show"
                            autocomplete="off" autocapitalize="none" autocorrect="off" />
                        
                        <password :value="newpass"
                            :strength-meter-only="true"
                            strength-meter-class="strengthMeterClass"
                            @score="getScore" />

                        <input type="checkbox" id="personal-show" class="hidden-visually" name="show" />
                        <label for="personal-show" class="personal-show-label"></label>
                    </div>
                
                    <template slot="popover">
                        <p>{{ scoreMessage }}</p>
                    </template>
                </v-popover>

				<button id="passwordbutton" :disabled="showloader === true" @click="changePassword">
                    {{ t('settings', 'Change password') }}
                </button>
                <span v-if="showloader" class='password-loading icon icon-loading-small password-state'></span>
			</form>
        </div>
    </div>
</template>

<script>
import Axios from 'nextcloud-axios'
import Password from 'vue-password-strength-meter'

export default {
	name: 'AuthSecurityPassword',
	components: {
		Password
	},
	data() {
		return {
			baseurl: OC.generateUrl('/settings/personal/changepassword'),
			oldpass: '',
			newpass: '',
			showloader: false,
			score: 0,
			scoreMessages: [
				t('settings', 'Very weak password'),
				t('settings', 'Weak password'),
				t('settings', 'So-so password'),
				t('settings', 'Good password'),
				t('settings', 'Strong password')
			]
		}
	},
	computed: {
		isOpen: function() {
			return this.newpass !== ''
		}
	},
	methods: {
		getScore(score) {
			this.score = score
		},
		scoreMessage() {
			return this.scoreMessages[this.score]
		},
		changePassword() {
			if (this.oldpass === this.newpass || this.newpass === '') {
				OC.msg.finishedSaving('#password-error-msg', {
					status: 'error',
					data: {
						message: t('settings', 'Unable to change password')
					}
				})
				return false
			}

			this.showloader = true
			const formParameters = {
				oldpassword: this.oldpass,
				newpassword: this.newpass,
				'newpassword-clone': this.newpass
			}
			const headers = {
				contentType: 'application/x-www-form-urlencoded charset=UTF-8'
			}

			return Axios.post(this.baseurl, formParameters, {
				headers: headers
			})
				.then(function(resp) {
					console.log(resp)
					OC.msg.finishedSaving('#password-error-msg', resp.data)
				})
				.then(() => (this.showloader = false))
				.catch(function(resp) {
					OC.msg.finishedSaving('#password-error-msg', {
						status: 'error',
						data: {
							message: t('settings', 'Unable to change password')
						}
					})
					this.showloader = false
				})
		}
	}
}
</script>
