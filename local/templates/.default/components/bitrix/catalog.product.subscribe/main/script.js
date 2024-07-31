(function (window) {

	if (!!window.JCCatalogProductSubscribe) {
		return;
	}

	var subscribeButton = function (params) {
		subscribeButton.superclass.constructor.apply(this, arguments);
		this.nameNode = BX.create('span', {
			props: {id: this.id},
			style: typeof (params.style) === 'object' ? params.style : {},
			text: params.text
		});
		this.buttonNode = BX.create('span', {
			attrs: {className: params.className},
			style: {marginBottom: '0', borderBottom: '0 none transparent'},
			children: [this.nameNode],
			events: this.contextEvents
		});
		if (BX.browser.IsIE()) {
			this.buttonNode.setAttribute("hideFocus", "hidefocus");
		}
	};
	BX.extend(subscribeButton, BX.PopupWindowButton);

	window.JCCatalogProductSubscribe = function (params) {
		this.buttonId = params.buttonId;
		this.buttonClass = params.buttonClass;
		this.jsObject = params.jsObject;
		this.ajaxUrl = '/bitrix/components/bitrix/catalog.product.subscribe/ajax.php';
		this.alreadySubscribed = params.alreadySubscribed;
		this.listIdAlreadySubscribed = params.listIdAlreadySubscribed;
		this.urlListSubscriptions = params.urlListSubscriptions;
		this.listOldItemId = {};
		this.landingId = params.landingId;

		this.elemButtonSubscribe = null;
		this.elemPopupWin = null;
		this.defaultButtonClass = 'bx-catalog-subscribe-button';

		this._elemButtonSubscribeClickHandler = BX.delegate(this.subscribe, this);
		this._elemHiddenClickHandler = BX.delegate(this.checkSubscribe, this);

		BX.ready(BX.delegate(this.init, this));
	};

	window.JCCatalogProductSubscribe.prototype.init = function () {
		if (!!this.buttonId) {
			this.elemButtonSubscribe = BX(this.buttonId);
			this.elemHiddenSubscribe = BX(this.buttonId + '_hidden');
		}

		if (!!this.elemButtonSubscribe) {
			BX.bind(this.elemButtonSubscribe, 'click', this._elemButtonSubscribeClickHandler);
		}

		if (!!this.elemHiddenSubscribe) {
			BX.bind(this.elemHiddenSubscribe, 'click', this._elemHiddenClickHandler);
		}

		this.setButton(this.alreadySubscribed);
		this.setIdAlreadySubscribed(this.listIdAlreadySubscribed);
	};

	window.JCCatalogProductSubscribe.prototype.checkSubscribe = function () {
		if (!this.elemHiddenSubscribe || !this.elemButtonSubscribe) return;

		if (this.listOldItemId.hasOwnProperty(this.elemButtonSubscribe.dataset.item)) {
			this.setButton(true);
		} else {
			BX.ajax({
				method: 'POST',
				dataType: 'json',
				url: this.ajaxUrl,
				data: {
					sessid: BX.bitrix_sessid(),
					checkSubscribe: 'Y',
					itemId: this.elemButtonSubscribe.dataset.item
				},
				onsuccess: BX.delegate(function (result) {
					if (result.subscribe) {
						this.setButton(true);
						this.listOldItemId[this.elemButtonSubscribe.dataset.item] = true;
					} else {
						this.setButton(false);
					}
				}, this)
			});
		}
	};

	// проверим введеный email
	window.JCCatalogProductSubscribe.prototype.validateEmail = function (email) {
		var re = /[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/;
		return re.test(String(email).toLowerCase());
	}

	//здесь начинается подписка
	window.JCCatalogProductSubscribe.prototype.subscribe = function () {
		this.elemButtonSubscribe = BX.proxy_context;
		if (!this.elemButtonSubscribe) return false;

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxUrl,
			data: {
				sessid: BX.bitrix_sessid(),
				subscribe: 'Y',
				itemId: this.elemButtonSubscribe.dataset.item,
				siteId: BX.message('SITE_ID'),
				landingId: this.landingId
			},
			onsuccess: BX.delegate(function (result) {

				if (result.success) {
					this.initPopupWindow();
					this.createSuccessPopup(result);

					//??
					this.setButton(true);
					this.listOldItemId[this.elemButtonSubscribe.dataset.item] = true;
					//??
				} else if (result.contactFormSubmit) {

					this.initPopupWindow();
					var emailStep = this.elemPopupWin.find('.js-subscribe-step-email')
					var successStep = this.elemPopupWin.find('.js-subscribe-step-success')
					var form = this.elemPopupWin.find('form');

					//подставляем ID сессии и ID текущего выбранного ТП
					this.elemPopupWin.find('input[name="itemId"]').val(this.elemButtonSubscribe.dataset.item);
					this.elemPopupWin.find('input[name="sessid"]').val(BX.bitrix_sessid());

					// останавливаем сабмит чтобы не было перезагрузки страницы
					form.submit(function (event) {
						event.preventDefault();
					});

					$(document).on("click", ".button-product-size-subscribe", BX.delegate(function () {

						var data = this.serializeArrayToOB(form.serializeArray());
						var emailInput  = this.elemPopupWin.find('input[name="contact[1][user]"]');
						var stepEmailErrorText  = this.elemPopupWin.find('.js-subscribe-error');
						var delayPrelouderTime = 400;

						window.siteShowPrelouder();
						stepEmailErrorText.fadeOut();
						emailInput.removeClass('error_input');

						// проверим введеный email
						if(!this.validateEmail(emailInput.val()))
						{
							console.log('Не верный email');
							stepEmailErrorText.text('Не верный email');
							stepEmailErrorText.fadeIn();
							emailInput.addClass('error_input');

							window.setTimeout(function() {window.siteHidePrelouder();}, delayPrelouderTime);
							//window.siteHidePrelouder();

							return false;
						}

						BX.ajax({
							method: 'POST',
							url: this.ajaxUrl,
							processData: true,
							data: data,
							onsuccess: BX.delegate(function (resultForm) {

								resultForm = BX.parseJSON(resultForm, {});
								if (resultForm.success) {
									//включаем слудющий шаг
									this.createSuccessPopup(resultForm);

									//??
									this.setButton(true);
									this.listOldItemId[this.elemButtonSubscribe.dataset.item] = true;
									//??

									//отчистим инпут
									emailInput.val('');

									//window.siteHidePrelouder()
									window.setTimeout(function() {window.siteHidePrelouder();}, delayPrelouderTime);

								} else if (resultForm.error) {

									///?
									if (resultForm.hasOwnProperty('setButton')) {
										this.listOldItemId[this.elemButtonSubscribe.dataset.item] = true;
										this.setButton(true);
									}
									//??

									//тут выводим ошибку
									var errorMessage = resultForm.message;
									if (resultForm.hasOwnProperty('typeName')) {
										errorMessage = resultForm.message.replace('USER_CONTACT',
											resultForm.typeName);
									}
									alert(errorMessage);
									console.log(errorMessage);

									stepEmailErrorText.text(errorMessage);
									stepEmailErrorText.fadeIn();
									emailInput.addClass('error_input');

									//window.siteHidePrelouder();
									window.setTimeout(function() {window.siteHidePrelouder();}, delayPrelouderTime);
								}
							}, this),
							onfailure: function (data) {
								console.error(data);
								window.setTimeout(function() {window.siteHidePrelouder();}, delayPrelouderTime);
								//window.siteHidePrelouder();
							}
						});


					}, this));

					successStep.fadeOut();
					emailStep.fadeIn();

					this.elemPopupWin.addClass('_opened');

				} else if (result.error) {

					//??
					if (result.hasOwnProperty('setButton')) {
						this.listOldItemId[this.elemButtonSubscribe.dataset.item] = true;
						this.setButton(true);
					}
					//??

					console.log('ошибка')
					console.log(result)
					alert(result.message);

					//this.showWindowWithAnswer({status: 'error', message: result.message});
				}
			}, this)
		});
	};

	window.JCCatalogProductSubscribe.prototype.serializeArrayToOB = function(serializeArray)
	{
		var o = {};
		var a = serializeArray;
		$.each(a, function() {
			if (o[this.name]) {
				if (!o[this.name].push) {
					o[this.name] = [o[this.name]];
				}
				o[this.name].push(this.value || '');
			} else {
				o[this.name] = this.value || '';
			}
		});
		return o;
	};

	window.JCCatalogProductSubscribe.prototype.reloadCaptcha = function()
	{
		BX.ajax.get(this.ajaxUrl+'?reloadCaptcha=Y', '', function(captchaCode) {
			BX('captcha_sid').value = captchaCode;
			BX('captcha_img').src = '/bitrix/tools/captcha.php?captcha_sid='+captchaCode+'';
		});
	};

	window.JCCatalogProductSubscribe.prototype.selectContactType = function(contactTypeId, event)
	{
		var contactInput = BX('bx-catalog-subscribe-form-container-'+contactTypeId), visibility = '',
			checkboxInput = BX('bx-contact-checkbox-'+contactTypeId);
		if(!contactInput)
		{
			return false;
		}

		if(checkboxInput != event.target)
		{
			if(checkboxInput.checked)
			{
				checkboxInput.checked = false;
			}
			else
			{
				checkboxInput.checked = true;
			}
		}

		if (contactInput.currentStyle)
		{
			visibility = contactInput.currentStyle.display;
		}
		else if (window.getComputedStyle)
		{
			var computedStyle = window.getComputedStyle(contactInput, null);
			visibility = computedStyle.getPropertyValue('display');
		}

		if(visibility === 'none')
		{
			BX('bx-contact-use-'+contactTypeId).value = 'Y';
			BX.style(contactInput, 'display', '');
		}
		else
		{
			BX('bx-contact-use-'+contactTypeId).value = 'N';
			BX.style(contactInput, 'display', 'none');
		}
	} ;


	window.JCCatalogProductSubscribe.prototype.createSuccessPopup = function(result)
	{
		var emailStep = this.elemPopupWin.find('.js-subscribe-step-email');
		var successStep = this.elemPopupWin.find('.js-subscribe-step-success');

		emailStep.hide();
		successStep.find('p').text(result.message);
		successStep.show();

		this.elemPopupWin.addClass('_opened');
	};

	window.JCCatalogProductSubscribe.prototype.initPopupWindow = function()
	{
		this.elemPopupWin = $('.popup-product-back-in-stock');
	};

	window.JCCatalogProductSubscribe.prototype.setButton = function(statusSubscription)
	{
		this.alreadySubscribed = Boolean(statusSubscription);
		if(this.alreadySubscribed)
		{
			this.elemButtonSubscribe.className = this.buttonClass + ' ' + this.defaultButtonClass + ' disabled';
			this.elemButtonSubscribe.innerHTML = '<span>' + BX.message('CPST_TITLE_ALREADY_SUBSCRIBED') + '</span>';
			BX.unbind(this.elemButtonSubscribe, 'click', this._elemButtonSubscribeClickHandler);
		}
		else
		{
			this.elemButtonSubscribe.className = this.buttonClass + ' ' + this.defaultButtonClass;
			this.elemButtonSubscribe.innerHTML = '<span>' + BX.message('CPST_SUBSCRIBE_BUTTON_NAME') + '</span>';
			BX.bind(this.elemButtonSubscribe, 'click', this._elemButtonSubscribeClickHandler);
		}
	};

	window.JCCatalogProductSubscribe.prototype.setIdAlreadySubscribed = function(listIdAlreadySubscribed)
	{
		if (BX.type.isPlainObject(listIdAlreadySubscribed))
		{
			this.listOldItemId = listIdAlreadySubscribed;
		}
	};

	window.JCCatalogProductSubscribe.prototype.showWindowWithAnswer = function(answer)
	{
		answer = answer || {};
		if (!answer.message) {
			if (answer.status == 'success') {
				answer.message = BX.message('CPST_STATUS_SUCCESS');
			} else {
				answer.message = BX.message('CPST_STATUS_ERROR');
			}
		}
		var messageBox = BX.create('div', {
			props: {
				className: 'bx-catalog-subscribe-alert'
			},
			children: [
				BX.create('span', {
					props: {
						className: 'bx-catalog-subscribe-aligner'
					}
				}),
				BX.create('span', {
					props: {
						className: 'bx-catalog-subscribe-alert-text'
					},
					text: answer.message
				}),
				BX.create('div', {
					props: {
						className: 'bx-catalog-subscribe-alert-footer'
					}
				})
			]
		});
		var currentPopup = BX.PopupWindowManager.getCurrentPopup();
		if(currentPopup) {
			currentPopup.destroy();
		}
		var idTimeout = setTimeout(function () {
			var w = BX.PopupWindowManager.getCurrentPopup();
			if (!w || w.uniquePopupId != 'bx-catalog-subscribe-status-action') {
				return;
			}
			w.close();
			w.destroy();
		}, 3500);
		var popupConfirm = BX.PopupWindowManager.create('bx-catalog-subscribe-status-action', null, {
			content: messageBox,
			onPopupClose: function () {
				this.destroy();
				clearTimeout(idTimeout);
			},
			autoHide: true,
			zIndex: 2000,
			className: 'bx-catalog-subscribe-alert-popup'
		});
		popupConfirm.show();
		BX('bx-catalog-subscribe-status-action').onmouseover = function (e) {
			clearTimeout(idTimeout);
		};
		BX('bx-catalog-subscribe-status-action').onmouseout = function (e) {
			idTimeout = setTimeout(function () {
				var w = BX.PopupWindowManager.getCurrentPopup();
				if (!w || w.uniquePopupId != 'bx-catalog-subscribe-status-action') {
					return;
				}
				w.close();
				w.destroy();
			}, 3500);
		};
	};

})(window);
