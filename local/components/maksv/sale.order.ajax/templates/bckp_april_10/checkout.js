BX.namespace('BX.Sale.Checkout');

(function() {
    'use strict';


    const instances = []
    BX.Sale.Checkout = class Checkout {

        static PROPS_SORT = (a, b) => (a['SORT'] * 1) - (b['SORT'] * 1)
        static AJAX_URL = '/local/components/belleyou/sale.order.ajax/ajax.php'
        static ACTION_SAVE_ORDER = 'saveOrderAjax'
        static ACTION_REFRESH_ORDER = 'refreshOrderAjax'
        static ADDRESS_MODE_DELIVERY = 'delivery'
        static ADDRESS_MODE_PVZ = 'pvz'
        static ADDRESS_MODE_BRANCH = 'branch'
        static ADDRESS_MODE_NO_DELIVERY = 'noDelivery'
        static CLASS_ERROR_INPUT = 'error_input' // used in validation


        args

        /**
         * Checkout container
         * @type Element
         */
        rootElement

        /**
         * Personal fields container
         * @type Element
         */
        personal

        /**
         * Delivery country&city fields container
         * @type Element
         */
        deliveryRegion
        /**
         * @type Element
         */
        cityField
        /**
         * @type Element
         */
        cityDadataField
        /**
         * @type Element
         */
        cityHiddenField

        /**
         * Select with all countries
         * @type Element
         */
        countrySelect

        /**
         * Delivery methods container
         * @type Element
         */
        deliveryMethods

        /**
         * Delivery address container
         */
        deliveryAddress
        /**
         * Inside deliveryAddress
         * @type Element
         */
        deliveryServices

        /**
         * @type Element
         */
        deliveryPvz

        /**
         * @type Element
         */
        deliveryBranch
        /**
         * @type Element
         */
        deliveryBranchPopup = null

        /**
         * Payment methods container
         * @type Element
         */
        paymentMethods

        /**
         * Podeli widget
         * @type Element
         */
        podeli

        /**
         * Cart products container
         * @type Element
         */
        cartProducts

        /**
         * Order totals container
         * @type Element
         */
        totals

        /**
         * @type Element
         */
        checkoutButton


        personalGroupId = '1'
        deliveryGroupId = '2'
        locationFieldCode = 'LOCATION'
        cityFieldCode = 'CITY'
        delayBeforeUpdate = 400

        /**
         * Available countries
         * @type Object
         */
        countries = {}
        currentCountry

        currentDelivery
        currentPayment
        currentBranch = null
        currentAddress = {}

        updateTimeout
        isUpdating
        updateIndicationIn
        updateIndicationOut

        zipInputs = {}
        dadataInputs = {}

        services = {}

        /**
         * Object with hashes. Used to prevent unsufficient re-rendering
         * @type Object
         */
        checksums

        /**
         * @type Object
         */
        options

        /**
         * @type Storage
         */
        storage

        allowedDeliveryMethods = {}

        constructor(rootElement, options = {}) {
            this.rootElement = rootElement
            this.args = {}
            this.registerOptions(options)

            this.personal = this.getPart('form.personal')
            this.deliveryRegion = this.getPart('form.delivery-region')
            this.cityField = null
            this.countrySelect = this.getPart('form.delivery-region.countries')
            this.deliveryMethods = this.getPart('form.delivery-methods')
            this.deliveryAddress = this.getPart('form.delivery-address')
            this.deliveryServices = this.getPart('form.delivery-address.services')
            this.deliveryPvz = this.getPart('form.delivery-pvz')
            this.deliveryBranch = this.getPart('form.delivery-branch')
            this.paymentMethods = this.getPart('form.pay-systems')
            this.podeli = this.getPart('podeli')
            this.cartProducts = this.getPart('cart.products')
            this.totals = this.getPart('totals')
            this.checkoutButton = this.getPart('checkout-button')
            this.checkoutButton.addEventListener('click', e => {
                e.preventDefault()
                this.processOrder()
            })

            this.currentCountry = this.getCountryFromStorage()
            this.countrySelect.querySelectorAll('option').forEach(option => {
                this.countries[option.value] = option.innerText
                if(option.selected && !this.currentCountry){
                    this.currentCountry = option.value
                }else if(this.currentCountry === option.value){
                    option.selected = true
                }
            })

            this.currentDelivery = null
            this.currentBranch = this.getFromStorage('checkoutCurrentBranch') ?? null

            this.updateTimeout = null
            this.isUpdating = false
            this.updateIndicationIn = null
            this.updateIndicationOut = null

            this.checksums = {}
        }

        registerOptions(options){
            this.options = {}

            switch (options.storage){
                case 'session':
                    this.storage = sessionStorage
                    break
                case 'local':
                default:
                    this.storage = localStorage
            }
            this.options.storageKey = options.storageKey ?? ('sale.order.ajax.checkout.' + Math.ceil(Math.random() * 1e7))
            this.options.cacheLifetime = options.cacheLifetime ?? (7*24*3600*1000)

            this.options.preloader = options.preloaderElementSelector
                ? document.querySelector(options.preloaderElementSelector)
                : null

            this.options.dadataToken = options.dadataToken ?? null

            this.options.podeliPaymentsCount = options.podeliPaymentsCount ?? 0
            this.options.podeliPaymentsInterval = options.podeliPaymentsInterval ?? 0
            this.options.addressFields = options.addressFields ?? {}
            this.options.branchPropCode = options.branchPropCode ?? null

            this.options.phoneMasks = (typeof options.phoneMasks === 'object' && options.phoneMasks) ? options.phoneMasks : {}

            this.options.certificate_ids = (typeof options.certificate_ids === 'object' && options.certificate_ids.length) ? options.certificate_ids : []
            this.options.cashForCountries = (typeof options.cashForCountries === 'object' && options.cashForCountries.length) ? options.cashForCountries : []

            const {userHash} = options
            this.maybeClearCache(userHash)

            console.log('options registered', this.options)
        }

        /**
         * @param rootElement
         * @param options
         * @returns Checkout
         */
        static instance(rootElement, options = {}){
            if(typeof rootElement === 'string'){
                rootElement = document.getElementById(rootElement)
            }
            for(let i = 0; i < instances.length; i++){
                if(instances[i].rootElement === rootElement){
                    return instances[i].instance
                }
            }
            const checkout = new BX.Sale.Checkout(rootElement, options)
            const instance = {
                rootElement, instance: checkout, options,
            }
            instances.push(instance)
            return checkout
        }

        firstRender = (args = null, allowCache = false) => {
            // ORDER DESCRIPTION
            const comment = this.getPart('form.delivery-address.comment.field', this.deliveryAddress)
            if(comment){
                comment.addEventListener('change', () => this.update())
            }

            if(allowCache){
                console.log('initial args', args)
                try{
                    const {order, date} = this.getFromStorage()
                    const currentDate = (new Date()).getTime()
                    const maxDate = (date * 1) + this.options.cacheLifetime
                    if(order && currentDate < maxDate){
                        args = order
                    }else{
                        console.log('cannot load from cache', {order, date})
                    }
                    this.restoreApishipFields()
                }catch (e){
                    console.error(e)
                }
            }
            return this.renderFromArgs(args)
                .then(data => {
                    //setTimeout(() => that.restoreApishipFields(), 300)
                    try{
                        this.getPart('form.delivery-pvz.selected.clear').addEventListener('click', () => {
                            console.log('clear pvz')
                            IPOLapiship_pvz.selectPVZ()
                        })
                    }catch (e){
                        console.error(e)
                    }
                    return Promise.resolve(this.update()).then(() => data)
                })
        }

        restoreApishipFields(){
            // const apishipFields = this.getFromStorage('checkoutApishipFields')
            // const apishipPvzId = this.getFromStorage('apishipPvzId')
            // try{
            //     Object.keys(apishipFields).forEach(key => {
            //         const value = apishipFields[key]
            //         const el = this.rootElement.querySelector('[name=' + key + ']')
            //         if(el){
            //             el.value = value
            //         }
            //     })
            //     console.log(apishipFields, apishipFields.apiship_pvzID)
            //     if(apishipPvzId){
            //         IPOLapiship_pvz.choozePVZ(apishipPvzId)
            //     }
            // }catch (e){}
        }

        renderFromArgs = (args) => {
            this.args = args
            console.log('renderFromArgs', args)

            // prepare prop collections
            const personalProps = []
            const deliveryProps = []
            const props = args['ORDER_PROP'].properties.sort(Checkout.PROPS_SORT)
            props.forEach(prop => {
                if(prop['ACTIVE'] !== 'Y' || prop['UTIL'] === 'Y'){
                    return
                }
                const groupId = prop['PROPS_GROUP_ID'] + ''
                if(groupId === this.personalGroupId){
                    personalProps.push(prop)
                }else if(groupId === this.deliveryGroupId){
                    deliveryProps.push(prop)
                }
            })

            return Promise.all([
                new Promise(resolve => {
                    this.renderPersonalProps(personalProps)
                    resolve()
                }),
                new Promise(resolve => {
                    this.renderDeliveryProps(deliveryProps)
                    resolve()
                }),
                new Promise(resolve => {
                    this.renderDadataFields()
                    resolve()
                }),
                new Promise(resolve => {
                    this.renderDeliveryMethods(args['DELIVERY'] ?? [])
                    resolve()
                }),
                new Promise(resolve => {
                    try{
                        this.renderDeliveryFields(deliveryProps, args)
                    }catch (e){
                        console.error(e)
                    }
                    resolve()
                }),
                new Promise(resolve => {
                    this.renderPaymentMethods(args['PAY_SYSTEM'] ?? [])
                    resolve()
                }),
                new Promise(resolve => {
                    this.renderCart(args)
                    resolve()
                }),
                new Promise(resolve => {
                    this.renderTotals(args['TOTAL'])
                    resolve()
                }),
                new Promise(resolve => {
                    this.renderPodeli(args['TOTAL'])
                    resolve()
                })
            ])
                .then(anyData => {
                    try{
                        this.validate()
                    }catch (e){}
                    return anyData
                })
        }

        renderPersonalProps(personalProps){
            if(this.isFocusedOnPersonal()){
                return
            }

            this.personal.innerHTML = ''
            personalProps.forEach(prop => {
                let placeholder = prop['NAME']
                const isRequired = (prop['REQUIRED'] === 'Y')
                if(isRequired){
                    placeholder += '*'
                }
                const input = BX.create({
                    tag: 'input',
                    props: {
                        type: (prop['IS_EMAIL'] === 'Y') ? 'email' : 'text',
                        name: 'ORDER_PROP_' + prop['ID'],
                        placeholder: placeholder,
                        className: 'form-input',
                        value: prop['VALUE'][0] ?? prop['DEFAULT_VALUE'],
                        required: isRequired,
                    },
                    attrs: {
                        'data-checkout': 'form.personal.field',
                    },
                    events: {
                        //change: () => this.update(),
                        focus: () => {
                            input._value = input.value
                        },
                        blur: () => {
                            if(input._value !== input.value){
                                delete input._value
                                this.update()
                            }
                        },
                    },
                })

                if(prop['IS_PHONE'] === 'Y'){
                    try{
                        const {mask, prefix} = this.options.phoneMasks[this.currentCountry] ?? {}
                        if(mask && prefix){
                            const maskedInput = new BX.MaskedInput({
                                mask: mask,
                                input: input,
                                placeholder: '_',
                            })
                            input.maskedInput = maskedInput
                            let value = prop['VALUE'][0] ?? prop['DEFAULT_VALUE']
                            if(typeof value === 'string'){
                                value = value.trim().replace(/\D/g, '').replace(new RegExp('^' + prefix), '')
                                maskedInput.setValue(value)
                            }
                        }
                    }catch (e){}
                }

                const row = BX.create({
                    tag: 'div',
                    props: {className: 'form-row'},
                    children: [input],
                })
                this.personal.append(row)
            })
        }

        renderDeliveryProps(deliveryProps){
            // if(this.isFocusedOn('form.delivery-region')){
            //     return
            // }
            this.deliveryRegion.innerHTML = ''

            const {city, dadataLocation} = this.getLocationProps(deliveryProps)
            const {location} = this.getDadataProps()

            console.log('country input', {city, countries: this.countries})
            const locationField = BX.create({
                tag: 'div',
                props: {
                    className: 'dropdown dropdown-contry',
                },
                children: [
                    BX.create({
                        tag: 'div',
                        props: {
                            className: 'dropdown-select',
                        },
                        attrs: {
                            'data-checkout': 'indicator.country-name',
                        },
                        text: this.countries[this.currentCountry] ?? '',
                    }),
                    BX.create({
                        tag: 'ul',
                        props: {
                            className: 'dropdown-box',
                        },
                        children: Object.keys(this.countries).map(country => {
                            const option = BX.create({
                                tag: 'li',
                                props: {
                                    className: 'dropdown-option',
                                },
                                text: this.countries[country],
                            })
                            option.addEventListener('click', e => {
                                e.preventDefault()
                                this.selectCountry(country)
                            })
                            return option
                        }),
                    })
                ],
            })

            this.cityField = BX.create({
                tag: 'input',
                props: {
                    className: 'form-input',
                    type: 'text',
                    placeholder: 'Населенный пункт, например, Москва' ?? '',
                    value: location['VALUE'][0] ?? location['DEFAULT_VALUE'],
                    name: 'ORDER_PROP_' + location['ID'],
                },
                events: {
                    change: () => this.update(),
                },
            })
            
            console.log("location");
            console.log(location);

            this.cityHiddenField = BX.create({
                tag: 'input',
                props: {
                    type: 'hidden',
                    name: 'ORDER_PROP_' + city['ID'],
                    value: city['VALUE'][0] ?? '',
                }
            })

            const cityField = BX.create({
                tag: 'div',
                props: {
                    className: 'form-row',
                },
                children: [
                    this.cityField
                ],
            })

            this.deliveryRegion.append(locationField, cityField, this.cityHiddenField)

            const that = this
            let dadataCountryParam = this.countries[this.currentCountry] ?? "Россия"
            if(dadataCountryParam.toLowerCase() === 'кыргызстан'){
                dadataCountryParam = 'Киргизия'
            }
            jQuery(this.cityField).suggestions({
                token: this.options.dadataToken,
                type: "ADDRESS",
                bounds: "city-settlement",
                constraints: {
                    locations: {
                        //country_iso_code: "RU",
                        country: dadataCountryParam,
                    },
                },
                onSuggestionsFetch: function (suggestions) {
                    console.log('onSuggestionsFetch', suggestions)
                    return suggestions.filter(function(suggestion) {
                        return suggestion.data.fias_level !== "5" && suggestion.data.fias_level !== "65";
                    });
                },
                onSelect: BX.proxy(function(suggestion)
                {
                    console.log('onSelect', suggestion)
                    Object.keys(that.zipInputs).forEach(key => {
                        const field = that.zipInputs[key]
                        if(field){
                            field.value = suggestion.data.postal_code ?? field.value
                        }
                    })
                    that.cityHiddenField.value = '' // suggestion.data.fias_id
                    IPOLapiship_pvz.pvzId = false
                    IPOLapiship_pvz.pvzAdress = false

                    that.dadataInputs.location.value = that.cityField.value
                    that.dadataInputs.fias.value = suggestion.data.fias_id
                    that.dadataInputs.zip.value = suggestion.data.postal_code

                    that.getRegionCodeByCityName(suggestion.data.city)
                        .then((code) => {
                            console.log({code})
                            that.cityHiddenField.value = (typeof code === 'string') ? code : ''
                            if(code){
                                that.saveToStorage(suggestion.data.city, 'checkoutSelectedCityName')
                            }else{
                                that.saveToStorage('', 'checkoutSelectedCityName')    
                            }
                            that.update()
                        })
                }, this),
                formatSelected: function(suggestion) {
                    let strLocation = '',
                        dataProps = ['settlement_with_type', 'city_with_type', 'area_with_type', 'region_with_type', 'country'];

                    for (let i = 0; i < dataProps.length; i++)
                    {
                        if (suggestion.data[dataProps[i]] && suggestion.data[dataProps[i]].length > 0)
                        {
                            if (dataProps[i] == 'city_with_type'
                                && suggestion.data[dataProps[i]] == suggestion.data.region_with_type
                                && suggestion.data.settlement_with_type && suggestion.data.settlement_with_type.length > 0)
                            {
                                continue;
                            }

                            if (dataProps[i] == 'region_with_type'
                                && suggestion.data[dataProps[i]] == suggestion.data.city_with_type
                                && !suggestion.data.settlement_with_type)
                            {
                                continue;
                            }

                            if (strLocation.length)
                                strLocation += ', ';

                            strLocation += suggestion.data[dataProps[i]];
                        }
                    }

                    return strLocation;
                }
            })
        }

        renderDadataFields(){
            const {location, fias, zip} = this.getDadataProps()

            const dadata = this.getPart('dadata')
            if(dadata){
                dadata.innerHTML = ''

                this.dadataInputs = {
                    location: BX.create({
                        tag: 'input',
                        props: {
                            name: 'ORDER_PROP_' + location['ID'],
                            type: 'hidden',
                            value: location['VALUE'][0] ?? '',
                        },
                        attrs: {
                            'data-checkout': 'dadata.field',
                        },
                    }),
                    fias: BX.create({
                        tag: 'input',
                        props: {
                            name: 'ORDER_PROP_' + fias['ID'],
                            type: 'hidden',
                            value: fias['VALUE'][0] ?? '',
                        },
                        attrs: {
                            'data-checkout': 'dadata.field',
                        },
                    }),
                    zip: BX.create({
                        tag: 'input',
                        props: {
                            name: 'ORDER_PROP_' + zip['ID'],
                            type: 'hidden',
                            value: zip['VALUE'][0] ?? '',
                        },
                        attrs: {
                            'data-checkout': 'dadata.field',
                        },
                    }),
                }

                dadata.append(
                    this.dadataInputs.location,
                    this.dadataInputs.fias,
                    this.dadataInputs.zip,
                )
            }
        }

        renderDeliveryMethods(deliveryMethods){
            this.deliveryMethods.innerHTML = ''

            const currentMainDelivery = this.getSelectedDelivery()
            const {onlyCertificates} = this.analyzeCart()

            let hasChecked = false
            const deliveryInputs = []
            this.allowedDeliveryMethods = {}
            Object.keys(deliveryMethods).forEach(key => {
                const delivery = deliveryMethods[key]
                const id = 'delivery-way-' + delivery['ID']
                const isChecked = (delivery['CHECKED'] === 'Y') // (currentMainDelivery === delivery['ID'] * 1)

                const mode = this.getAddressModeByDelivery(delivery['ID'])
                if(onlyCertificates && mode !== Checkout.ADDRESS_MODE_NO_DELIVERY){
                    // в корзине только виртуальные товары, а доставка их не поддерживает
                    return
                }else if(!onlyCertificates && mode === Checkout.ADDRESS_MODE_NO_DELIVERY){
                    // выбрана доставка для виртуальных товаров (Без доставки), а в корзине есть обычные товары
                    return
                }
                this.allowedDeliveryMethods[key] = delivery
                if(isChecked){
                    hasChecked = true
                }

                const input = BX.create({
                    tag: 'input',
                    props: {
                        className: 'input-radio',
                        type: 'radio',
                        name: delivery['FIELD_NAME'],
                        id: id,
                        value: key,
                        checked: isChecked,
                    },
                    attrs: {
                        'data-checkout': 'form.delivery-methods.field',
                    },
                    events: {
                        change: () => {
                            this.selectDelivery(key)
                            this.update()
                        },
                    },
                })
                const label = BX.create({
                    tag: 'label',
                    props: {
                        className: 'label-radio',
                        for: id,
                    },
                    children:[
                        BX.create({
                            tag: 'strong',
                            text: delivery['OWN_NAME'],
                        }),
                        BX.create({
                            tag: 'span',
                            html: [delivery['PRICE_FORMATED'], delivery['DESCRIPTION']].filter(x => !!x).join(' / '),
                        }),
                    ],
                    events: {
                        click: () => input.click(),
                    },
                })

                const wrap = BX.create({
                    tag: (this.deliveryMethods.nodeName === 'UL') ? 'li' : 'div',
                    children: [input, label],
                })
                this.deliveryMethods.append(wrap)
                deliveryInputs.push(input)
            })

            console.log({hasChecked, deliveryInputs})
            if(!hasChecked && deliveryInputs.length){
                try{
                    deliveryInputs[0].checked = true
                    this.selectDelivery(deliveryInputs[0].value)
                    setTimeout(() => this.update(), 300)
                }catch (e){
                    console.error(e)
                }
            }
        }

        renderDeliveryFields(deliveryProps, args){
            let deliveryId = this.getSelectedDelivery()
            const mode = this.getAddressModeByDelivery(deliveryId)
            console.log({mode})
            if(mode === Checkout.ADDRESS_MODE_DELIVERY){
                this.deliveryAddress.style.display = 'block'
                this.deliveryPvz.style.display = ''
                this.deliveryBranch.style.display = ''
            }else if(mode === Checkout.ADDRESS_MODE_PVZ){
                this.deliveryAddress.style.display = ''
                this.deliveryPvz.style.display = 'block'
                this.deliveryBranch.style.display = ''

                this.renderDeliveryPvz(deliveryProps, args)
            }else if(mode === Checkout.ADDRESS_MODE_BRANCH){
                this.deliveryAddress.style.display = ''
                this.deliveryPvz.style.display = ''
                this.deliveryBranch.style.display = 'block'

                this.renderDeliveryBranch(deliveryProps, args)
            }else{
                this.deliveryAddress.style.display = ''
                this.deliveryPvz.style.display = ''
                this.deliveryBranch.style.display = ''
            }
            this.renderDeliveryAddress(deliveryProps, args)
        }

        renderDeliveryAddress(deliveryProps, args){
            if(this.isFocusedOn(this.deliveryAddress)){
                return
            }
            this.loadCurrentAddress()

            const wide = false
            const short = true
            const layout = [
                wide,
                short, short, short
            ]

            const addressForm = this.getPart('form.delivery-address.address', this.deliveryAddress)
            addressForm.innerHTML = ''

            //const {address} = this.getLocationProps(deliveryProps)
            if(deliveryProps && deliveryProps.length){
                let streetField = null
                let buildingField = null
                deliveryProps.forEach((field, i) => {
                    let fieldElement = null
                    if(field['TYPE'] === 'STRING' && field['MULTILINE'] === 'Y'){
                        fieldElement = BX.create({
                            tag: 'textarea',
                            props: {
                                value: this.currentAddress['ORDER_PROP_' + field['ID']] ?? '',
                            },
                            attrs: {
                                'data-checkout': 'form.delivery-address.address.field',
                                'required': 'required',
                                name: 'ORDER_PROP_' + field['ID'],
                            },
                            events: {
                                change: () => this.update(),
                            },
                        })
                    }else if(field['TYPE'] === 'STRING'){
                        fieldElement = BX.create({
                            tag: 'input',
                            props: {
                                className: 'form-input',
                                placeholder: field['NAME'] + (field['REQUIRED'] === 'Y' ? '*' : ''),
                            },
                            attrs: {
                                type: 'text',
                                value: this.currentAddress['ORDER_PROP_' + field['ID']] ?? '',
                                'data-checkout': 'form.delivery-address.address.field',
                                'required': 'required',
                                name: 'ORDER_PROP_' + field['ID'],
                            },
                            events: {
                                change: () => this.update(),
                            },
                        })
                        if(field['IS_ZIP'] === 'Y'){
                            this.zipInputs[field['ID']] = fieldElement
                            if(field['REQUIRED'] === 'Y'){
                                fieldElement.setAttribute('data-default', '-')
                            }
                        }
                    }else{
                        return
                    }
                    const row = BX.create({
                        tag: 'div',
                        props: {
                            className: 'form-row' + (i < layout.length && layout[i] ? ' short-row' : ''), // .short-row for some
                        },
                        children: [
                            fieldElement
                        ],
                    })
                    addressForm.append(row)

                    if(field['CODE'].toLowerCase().indexOf('street') !== -1){
                        streetField = {field, element: fieldElement}
                    }
                    if(field['CODE'].toLowerCase().indexOf('building') !== -1){
                        buildingField = {field, element: fieldElement}
                    }
                })

                // DADATA START
                try{
                    if(streetField){
                        console.log(this.dadataInputs)
                        const currentCountryName = this.countries[this.currentCountry] ?? "Россия"
                        let streetData = {
                            token: this.options.dadataToken,
                            type: "ADDRESS",
                            hint: false,
                            bounds: "street",
                            count: 5,
                            onSelect: (suggestion) => {
                                //updatePostalCode(suggestion.data.postal_code);
                                Object.keys(this.zipInputs).forEach(key => {
                                    const field = this.zipInputs[key]
                                    if(field){
                                        field.value = suggestion.data.postal_code ?? field.value
                                    }
                                })
                            }
                        }
                        console.log({currentCountryName})
                        if(currentCountryName.toLowerCase() === 'россия'){
                            streetData.constraints = {
                                locations: [
                                    {"city_fias_id": this.dadataInputs.fias.value},
                                    {"settlement_fias_id": this.dadataInputs.fias.value}
                                ]
                            }
                        }else if(currentCountryName.toLowerCase() === 'беларусь'){
                            streetData.constraints = {
                                locations: { country_iso_code: "BY" }
                            }
                            streetData.onSearchStart = (query) => {
                                query.query = this.cityField.value + ' ' + query.query
                            }
                        }else{ // только для РФ / РБ
                            streetData = null
                        }

                        console.log({streetData})
                        const $street = jQuery(streetField.element)
                        if(streetData){
                            $street.suggestions(streetData)

                            if(buildingField){
                                jQuery(buildingField.element).suggestions({
                                    token: this.options.dadataToken,
                                    type: "ADDRESS",
                                    hint: false,
                                    noSuggestionsHint: false,
                                    bounds: "house",
                                    constraints: $street,
                                    onSelect: (suggestion) => {
                                        Object.keys(this.zipInputs).forEach(key => {
                                            const field = this.zipInputs[key]
                                            if(field){
                                                field.value = suggestion.data.postal_code ?? field.value
                                            }
                                        })
                                    }
                                })
                            }
                        }
                    }
                }catch (e){
                    console.error('Unable to enable dadata hints for street', e)
                }
                // DADATA END
            }

            const comment = this.getPart('form.delivery-address.comment.field', this.deliveryAddress)
            if(comment){
                comment.value = (typeof this.currentAddress['ORDER_DESCRIPTION'] === 'string') ? this.currentAddress['ORDER_DESCRIPTION'] : ''
            }

            const servicesContainer = this.getPart('form.delivery-address.services.fields', this.deliveryServices)
            servicesContainer.innerHTML = ''
            console.log({services: this.services})
            if(this.services && servicesContainer){
                try{

                    // TEMP FIX - выводит самый дешёвый тариф доставки
                    // START TEMP FIX
                    let globalMinPrice = null
                    let globalCheapestDeliveryElement = null
                    let isTariffSelected = false
                    // END TEMP FIX

                    Object.keys(this.services).forEach(key => {
                        const service = this.services[key]
                        
                        console.log("service")
                        console.log(service)
                        console.log("service")                        
                        
                        const tariffList = service['TARIFFS'] ?? null
                        if(!tariffList || typeof tariffList !== 'object' || !tariffList.length){
                            return
                        }
                        const serviceSelected = !!service['SELECTED']

                        // START TEMP FIX
                        let minPrice = null
                        tariffList.forEach(tariff => {
                            if(minPrice === null){
                                minPrice = tariff['PRICE'] * 1
                            }else{
                                minPrice = Math.min(minPrice, tariff['PRICE'] * 1)
                            }
                        })
                        // END TEMP FIX
                        
                        tariffList.forEach((tariff, i) => {
                            //BY KZ TK FIX
                            if(this.currentCountry == "0000000001"){ //BY
                                if(tariff['PROVIDER'] === "dpd" || tariff['PROVIDER'] === "rupost" || tariff['PROVIDER'] === "boxberry"){
                                    return    
                                } 
                            }else if(this.currentCountry == "0000000276"){ //KZ
                                if(tariff['PROVIDER'] === "rupost" || tariff['PROVIDER'] === "boxberry"){
                                    return    
                                }
                            }                            
                            
                            const price = tariff['PRICE'] * 1
                            
                            // START TEMP FIX
                            if(minPrice === null || price > minPrice){
                                if(tariff['PROVIDER'] === "dpd" && tariff['NAME'] === "DPD EXPRESS"){
                                    tariff['PROVIDER'] = "DPD EXPRESS";    
                                }else{
                                    return    
                                }
                            }
                            
                            if(tariff['PROVIDER'] === "rupost" && tariff['NAME'] === "Курьер онлайн"){
                                tariff['PROVIDER'] = "Почта РФ";    
                            }
                            // END TEMP FIX

                            const selected = serviceSelected && !!tariff['SELECTED']
                            isTariffSelected = isTariffSelected || selected
                            const randId = 'courier-way-' + key + '-' + i
                            let html = ''
                            if(tariff['PRICE'] * 1 > 0){
                                html += Math.ceil(tariff['PRICE'] * 1) + ' ' + this.num_word(tariff['PRICE'] * 1, ['рубль', 'рубля', 'рублей'])
                            }else{
                                html += '<span class="green">БЕСПЛАТНО</span>'
                            }
                            html += ' '
                                + (tariff['TERM'] ? tariff['TERM'] : Math.ceil(tariff['DAYS_MAX'] * 1))
                                + ' '
                                + this.num_word(tariff['DAYS_MAX'], ['раб. день', 'раб. дня', 'раб. дней'])
                            html = tariff['PROVIDER'].toUpperCase() + ' (' + html + ')'

                            const input = BX.create({
                                tag: 'input',
                                props: {
                                    id: randId,
                                    type: 'radio',
                                    className: 'input-radio',
                                    name: 'DELIVERY_COURIER_WAY',
                                    value: tariff['ID'],
                                    checked: selected,
                                },
                                attrs: {
                                    'data-checkout': 'form.delivery-address.services.field',
                                    'data-apiship-service-provider': key,
                                },
                            })
                            const el = BX.create({
                                tag: 'li',
                                children: [
                                    input,
                                    BX.create({
                                        tag: 'label',
                                        props: {
                                            className: 'label-radio',
                                        },
                                        attrs: {
                                            for: randId,
                                        },
                                        html: html,
                                        events: {
                                            click: () => {
                                                if(!input.selected){
                                                    input.selected = true
                                                    this.update()
                                                }
                                            },
                                        },
                                    }),
                                ],
                            })
                            servicesContainer.append(el)

                            // START TEMP FIX
                            if(globalMinPrice === null || price < globalMinPrice){
                                globalMinPrice = price
                                globalCheapestDeliveryElement = input
                            }
                            // END TEMP FIX 
                        })
                    })

                    if(!isTariffSelected && globalCheapestDeliveryElement){
                        globalCheapestDeliveryElement.selected = true
                    }

                }catch (e){
                    console.error(e)
                }
            }
        }

        renderDeliveryBranch(deliveryProps, args){
            if(this.isFocusedOn(this.deliveryBranch)){
                console.log('is focused on deliveryBranch')
                return
            }
            const selected = this.getPart('form.delivery-branch.selected', this.deliveryBranch)
            const button = this.getPart('form.delivery-branch.button', this.deliveryBranch)

            if(!this.deliveryBranchPopup){
                this.deliveryBranchPopup = document.createElement('div')
                this.deliveryBranchPopup.style.display = 'none'
                this.deliveryBranch.append(this.deliveryBranchPopup)

                if(button){
                    button.addEventListener('click', e => {
                        console.log('click select branch')
                        e.preventDefault()
                        this.showBranchList()
                    })
                }
                const editButton = this.getPart('form.delivery-branch.selected.edit', this.deliveryBranch)
                if(editButton){
                    editButton.addEventListener('click', (e) => {
                        console.log('click edit branch')
                        e.preventDefault()
                        this.showBranchList()
                        if(selected){
                            selected.style.display = 'none'
                        }
                    })
                }
            }else{
                this.deliveryBranchPopup.style.display = 'none'
            }

            if(this.currentBranch && selected){
                selected.style.display = 'block'
                const name = this.getPart('form.delivery-branch.selected.name', this.deliveryBranch)
                const address = this.getPart('form.delivery-branch.selected.address', this.deliveryBranch)
                const term = this.getPart('form.delivery-branch.selected.term', this.deliveryBranch)

                name.innerText = this.currentBranch['NAME'] ?? ''
                address.innerText = this.currentBranch['ADDRESS'] ?? ''
                term.innerHTML = this.currentBranch['WORK_TIME'] ?? ''

                button.style.display = 'none'
            }else{
                button.style.display = ''
                selected.style.display = 'none'
            }
        }

        showBranchList(){
            this.deliveryBranchPopup.innerHTML = ''

            const button = this.getPart('form.delivery-branch.button', this.deliveryBranch)
            button.style.display = 'none'

            let cityName = this.getFromStorage('checkoutSelectedCityName')
            cityName = cityName ? cityName : ''
            if(!cityName){
                this.deliveryBranchPopup.style.display = 'none'
                return
            }

            this.deliveryBranchPopup.innerHTML = ''
            this.deliveryBranchPopup.style.display = 'block'

            fetch('/local/components/belleyou/sale.order.ajax/get_branches.php?cityName=' + cityName)
                .then(res => res.text())
                .then(res => {
                    this.deliveryBranchPopup.innerHTML = res
                    const branches = this.deliveryBranchPopup.querySelectorAll('#checkout-shops-list .shop-link')
                    branches.forEach(branch => {
                        branch.addEventListener('click', e => {
                            this.deliveryBranchPopup.style.display = 'none'

                            const branchData = JSON.parse(atob(branch.getAttribute('data-branch')))

                            let branchId = branch.getAttribute('data-branch-id') * 1
                            branchId = branchId ? (branchId + '') : null
                            if(branchId !== this.currentBranch){
                                this.setBranch(branchData)
                                this.update()
                            }
                        })
                    })
                })
        }

        setBranch(branchData){
            this.currentBranch = branchData
            this.saveToStorage(this.currentBranch, 'checkoutCurrentBranch')
        }

        renderDeliveryPvz(deliveryProps, args){
            const apishipPvzId = document.getElementById('apiship_pvzID')
            let pvz = null
            if(apishipPvzId && apishipPvzId.value){
                const pvzId = apishipPvzId.value + ''
                if(IPOLapiship_pvz.PVZ[pvzId]){
                    pvz = IPOLapiship_pvz.PVZ[pvzId]
                }
            }

            if(pvz){
                this.getPart('form.delivery-pvz.button').style.display = 'none'
                this.getPart('form.delivery-pvz.selected').style.display = 'block'
                this.getPart('form.delivery-pvz.selected.name').innerText = pvz.Name
                this.getPart('form.delivery-pvz.selected.address').innerText = pvz.Address
                this.getPart('form.delivery-pvz.selected.term').innerText = pvz.daysMax + ' ' + this.num_word(pvz.daysMax*1, ['рабочий день', 'рабочих дня', 'рабочих дней'])
            }else{
                this.getPart('form.delivery-pvz.button').style.display = ''
                this.getPart('form.delivery-pvz.selected').style.display = 'none'
            }
        }

        num_word(value, words){
            value = Math.abs(value) % 100;
            var num = value % 10;
            if(value > 10 && value < 20) return words[2];
            if(num > 1 && num < 5) return words[1];
            if(num == 1) return words[0];
            return words[2];
        }

        renderPaymentMethods(paymentMethods){
            this.paymentMethods.innerHTML = ''

            paymentMethods.sort(Checkout.PROPS_SORT).forEach(payment => {
                if(payment['ACTIVE'] !== 'Y'){
                    return
                }
                if(this.options.cashForCountries.indexOf(this.currentCountry) === -1){
                    if (payment['IS_CASH'] === 'Y'){
                        return
                    }
                    if((typeof payment['PSA_ACTION_FILE'] === 'string') && payment['PSA_ACTION_FILE'].indexOf('podeli') !== -1){
                        return
                    }
                    if((typeof payment['PSA_NAME'] === 'string') && payment['PSA_NAME'].toLowerCase().indexOf('подели') !== -1) {
                        return
                    }
                }

                const id = 'payment-system-' + payment['ID']
                const input = BX.create({
                    tag: 'input',
                    props: {
                        className: 'input-radio',
                        type: 'radio',
                        name: 'payment-system',
                        id: id,
                        value: payment['ID'],
                        checked: payment['CHECKED'] === 'Y',
                    },
                    attrs: {
                        'data-checkout': 'form.pay-systems.field',
                    },
                    events: {
                        change: () => {
                            this.currentPayment = payment['ID']
                            this.update()
                        },
                    },
                })
                if(input.checked){
                    this.currentPayment = payment['ID']
                }
                const label = BX.create({
                    tag: 'label',
                    props: {
                        className: 'label-radio',
                        for: id,
                    },
                    children:[
                        BX.create({
                            tag: 'strong',
                            text: payment['NAME'],
                        }),
                    ],
                    events: {
                        click: () => input.click(),
                    },
                })

                const wrap = BX.create({
                    tag: (this.paymentMethods.tagName === 'UL') ? 'li' : 'div',
                    children: [input, label],
                })
                this.paymentMethods.append(wrap)
            })
        }

        renderPodeli(totals){
            const wrapper = this.getPart('podeli.payments', this.podeli)
            const total = this.getPart('podeli.total', this.podeli)
            const podeliPaymentsCount = (this.options['podeliPaymentsCount'] ?? 0) * 1
            const podeliPaymentsInterval = (this.options['podeliPaymentsInterval'] ?? 0) * 1
            if(podeliPaymentsCount <= 0 || podeliPaymentsInterval <= 0){
                this.podeli.style.display = 'none'
                return
            }else if(!this.isPodeliPaymentSelected()){
                this.podeli.style.display = 'none'
            }else{
                this.podeli.style.display = ''
            }

            let totalPrice = totals['ORDER_TOTAL_PRICE'] ?? null
            if(totalPrice === null){
                return
            }
            totalPrice *= 1

            total.innerHTML = totals['ORDER_TOTAL_PRICE_FORMATED']
            wrapper.innerHTML = ''

            const step = totalPrice / podeliPaymentsCount
            let amountRemains = totalPrice
            const now = new Date()
            const dateOptions = {
                month: 'long',
                day: 'numeric',
            }
            for(let i = 0; i < podeliPaymentsCount; i++){
                const paymentAmount = (i === podeliPaymentsCount - 1)
                    ? Math.max(0, amountRemains)
                    : Math.max(0, Math.min(step, amountRemains))

                const li = BX.create({
                    tag: 'li',
                    html: `<span class="podeli-payment-status"></span>` +
                        `<span class="podeli-payment-date">${(i === 0) ? 'Сегодня' : now.toLocaleString('ru', dateOptions)}</span>` +
                        `<span class="podeli-payment-sum">${paymentAmount} ₽</span>`
                })
                wrapper.append(li)

                now.setDate(now.getDate() + podeliPaymentsInterval)
                amountRemains -= step
            }
        }

        renderCart(args){
            const argsHash = this.hash(args['GRID']['ROWS'] ?? null)
            if(this.checksums.cart === argsHash){
                // nothing changed
                return
            }
            this.checksums.cart = argsHash

            this.cartProducts.innerHTML = ''

            const items = args['GRID']['ROWS'] ?? {}
            Object.keys(items).forEach(key => {
                const product = items[key].data ?? null
                if(!product){
                    return
                }

                const itemElement = BX.create({
                    tag: (this.cartProducts.tagName === 'UL') ? 'li' : 'div',
                    props: {
                        className: 'checkout-item-preview',
                    },
                    children: [
                        BX.create({
                            tag: 'img',
                            props: {
                                src: product['DETAIL_PICTURE_SRC'] ?? product['DETAIL_PICTURE_SRC_ORIGINAL'],
                                alt: product['NAME'],
                            },
                            attrs: {
                                width: 100,
                                height: 150,
                            },
                        }),
                    ],
                })

                this.cartProducts.append(itemElement)
            })
        }

        renderTotals(totals){
            const subtotalIndicator = this.getPart('indicator.totals.subtotal', this.totals)
            subtotalIndicator.innerHTML = totals['ORDER_PRICE_FORMATED']

            const discountElement = this.getPart('totals.discount', this.totals)
            const discountIndicator = this.getPart('indicator.totals.discount', this.totals)
            const discountAmount = totals['DISCOUNT_PRICE'] * 1
            if(discountAmount === 0){
                discountElement.style.display = 'none'
                discountIndicator.innerHTML = ''
            }else{
                discountElement.style.display = ''
                discountIndicator.innerHTML = totals['DISCOUNT_PRICE_FORMATED']
            }

            const deliveryIndicator = this.getPart('indicator.totals.delivery', this.totals)
            deliveryIndicator.innerHTML = totals['DELIVERY_PRICE_FORMATED']

            const totalIndicator = this.getPart('indicator.totals.total', this.totals)
            totalIndicator.innerHTML = totals['ORDER_TOTAL_PRICE_FORMATED']
        }



        update(){
            console.log('update')
            if(!this.canUpdate()){
                console.log('cannot update 1')
                return
            }

            return new Promise((resolve, reject) => {
                if(!this.canUpdate()){
                    console.log('cannot update 2')
                    return
                }

                clearTimeout(this.updateTimeout)

                this.updateTimeout = setTimeout(() => {
                    const data = this.collectFormData({action: Checkout.ACTION_REFRESH_ORDER})
                    this.startUpdating()

                    // this.stopUpdating()
                    // return resolve()
                    BX.ajax.post(Checkout.AJAX_URL, data, (res) => {
                        const {order, apiship, IPOL_APISHIP2_serviceWidget} = JSON.parse(res)
                        if(!order || typeof order !== 'object'){
                            reject('response "order" field is empty')
                            return
                        }
                        if(apiship && apiship.ipolapiship_pvz_list_tag_ajax){
                            IPOLapiship_pvz.PVZ = apiship.ipolapiship_pvz_list_tag_ajax
                            if(!BX.Sale.OrderAjaxComponent){
                                BX.Sale.OrderAjaxComponent = {}
                            }
                            BX.Sale.OrderAjaxComponent.sendRequest = () => this.update()
                        }
                        if(apiship){
                            IPOLapiship_pvz.city = apiship.apiship_city
                            IPOLapiship_pvz.cityID = apiship.apiship_city_id
                            IPOLapiship_pvz.pvzId = apiship.apiship_pvzID
                        }

                        let services = {}
                        if(IPOL_APISHIP2_serviceWidget && IPOL_APISHIP2_serviceWidget.calculatedServices){
                            services = IPOL_APISHIP2_serviceWidget.calculatedServices
                            if(!Object.keys(services).length || typeof services !== 'object'){
                                services = {}
                            }
                        }
                        this.services = services

                        this.renderFromArgs(order)
                            .then(() => this.stopUpdating())
                            .then(() => {
                                this.saveToStorage({
                                    order,
                                    date: (new Date()).getTime(),
                                })
                            })
                            .catch(reason => {
                                this.stopUpdating()
                                reject(reason)
                            })
                    })
                }, this.delayBeforeUpdate)
            })
        }

        collectFormData({action}){
            const actionVariable = this.getPart('action-variable')
            const siteId = this.getPart('site-id')
            const signature = this.getPart('signature')

            let data = {
                sessid: BX.bitrix_sessid(), //sessid.value,
                signedParamsString: signature.value,
                via_ajax: 'Y',
                SITE_ID: siteId.value,
            }
            //const action = 'saveOrderAjax' // 'refreshOrderAjax'
            data[actionVariable.value] = action

            const order = this.collectOrderData()
            if(action === Checkout.ACTION_SAVE_ORDER){
                data = {...data, ...order}
            }else{
                data.order = order
            }


            console.log('collected data', data)
            return data
        }

        collectOrderData(){
            const actionVariable = this.getPart('action-variable')
            const locationType = this.getPart('location-type')
            const buyerStore = this.getPart('buyer-store')
            const personType = this.getPart('person-type')

            const deliveries = this.allowedDeliveryMethods ?? {}
            let defaultDelivery = Object.keys(deliveries)[0] ?? ''
            defaultDelivery = defaultDelivery ? deliveries[defaultDelivery] : ''
            defaultDelivery = defaultDelivery ? defaultDelivery['ID'] : ''
            if(typeof defaultDelivery !== 'string' && typeof defaultDelivery !== 'number'){
                defaultDelivery = ''
            }
            //console.log(deliveries, {defaultDelivery, del_id: this.getSelectedDelivery(defaultDelivery)})

            const mode = this.getAddressModeByDelivery(this.getSelectedDelivery())

            const order = {
                sessid: BX.bitrix_sessid(), //sessid.value,
                location_type: locationType.value,
                BUYER_STORE: buyerStore.value,
                PERSON_TYPE: personType.value,

                DELIVERY_ID: this.getSelectedDelivery(defaultDelivery),
                PAY_SYSTEM_ID: this.currentPayment,
            }
            order[actionVariable.value] = Checkout.ACTION_SAVE_ORDER

            // set personal fields
            this.getPersonalFields().forEach(field => {
                let value
                if(field.maskedInput){
                    value = field.maskedInput.getValue()
                }else{
                    value = field.value
                }
                order[field.getAttribute('name')] = value
            })

            // set address fields
            this.getAddressFields().forEach(field => {
                const defaultValue = field.hasAttribute('data-default') ? field.getAttribute('data-default') : ''
                if(mode === Checkout.ADDRESS_MODE_DELIVERY){
                    order[field.getAttribute('name')] = field.value ? field.value : defaultValue
                }else{
                    order[field.getAttribute('name')] = ''
                }
                this.currentAddress[field.getAttribute('name')] = field.value ? field.value : defaultValue
            })
            const comment = this.getPart('form.delivery-address.comment.field', this.deliveryAddress)
            if(mode === Checkout.ADDRESS_MODE_DELIVERY) {
                order['ORDER_DESCRIPTION'] = (comment && comment.value) ? comment.value : ''
            }else{
                order['ORDER_DESCRIPTION'] = ''
            }
            this.currentAddress['ORDER_DESCRIPTION'] = (comment && comment.value) ? comment.value : ''

            this.saveCurrentAddress()
            // this.saveToStorage(comment ? comment.value : null, 'ORDER_DESCRIPTION')

            this.getDadataFields().forEach(field => {
                order[field.getAttribute('name')] = field.value ? field.value : ''
            })

            const apishipFields = {}
            this.rootElement.querySelectorAll('input[name^=apiship]').forEach(input => {
                order[input.getAttribute('name')] = input.value
                apishipFields[input.getAttribute('name')] = input.value
            })
            this.saveToStorage(apishipFields, 'checkoutApishipFields')
            let apishipPvzId = this.rootElement.querySelector('input[name=apiship_pvzID]')
            if(apishipPvzId){
                apishipPvzId = apishipPvzId.value
                if(!apishipPvzId){
                    apishipPvzId = false
                }
            }
            if(apishipPvzId){
                this.saveToStorage(apishipPvzId, 'apishipPvzId')
            }

            const {city, branch, apishipPickup} = this.getLocationProps(this.args['ORDER_PROP'].properties)
            order['ORDER_PROP_' + city['ID']] = this.cityHiddenField.value
            if(apishipPickup && apishipPickup['ID']){
                order['ORDER_PROP_' + apishipPickup['ID']] = '-'
            }
            if(branch && branch['ID']){
                order['ORDER_PROP_' + branch['ID']] = ''
            }
            console.log('apiship test', {mode, needle: Checkout.ADDRESS_MODE_DELIVERY})
            if(mode === Checkout.ADDRESS_MODE_DELIVERY){
                console.log('apiship test', {parts: this.getParts('form.delivery-address.services.field')})
                this.getParts('form.delivery-address.services.field').forEach(input => {
                    console.log('apiship test', {input, checked: input.checked})
                    if(input.checked){
                        console.log('apiship test', {attr: input.getAttribute('data-apiship-service-provider'), value: input.value * 1})
                        order['IPOL_APISHIP2_serviceProviderKey'] = input.getAttribute('data-apiship-service-provider')
                        order['IPOL_APISHIP2_serviceTariffId'] = input.value * 1
                        //order['DELIVERY_ID'] = input.value * 1
                    }
                })
            }else if(mode === Checkout.ADDRESS_MODE_PVZ){
                const pvz = IPOLapiship_pvz.pvzAdress ?? null
                if(pvz && typeof pvz === 'string' && apishipPickup && apishipPickup['ID']){
                    order['ORDER_PROP_' + apishipPickup['ID']] = pvz
                }
            }else if(mode === Checkout.ADDRESS_MODE_BRANCH){
                if(this.currentBranch && branch && branch['ID']){
                    order['ORDER_PROP_' + branch['ID']] = this.currentBranch
                        ? (this.currentBranch['NAME'] + ' (' + this.currentBranch['ADDRESS'] + ')')
                        : ''
                }
            }

            return order
        }

        processOrder(){
            return new Promise((resolve, reject) => {
                if(!this.canUpdate()){
                    console.log('cannot update 2')
                    return
                }

                const isValid = this.validate()
                if(!isValid){
                    console.log('validation failed')
                    return
                }

                clearTimeout(this.updateTimeout)

                this.updateTimeout = setTimeout(() => {
                    this.startUpdating()

                    const data = this.collectFormData({action: Checkout.ACTION_SAVE_ORDER})
                    console.log('save order args', data)
                    BX.ajax.post(Checkout.AJAX_URL, data, (res) => {
                        res = JSON.parse(res)
                        
                        if(typeof res.order.REDIRECT_URL !== "undefined"){
                            location.href = res.order.REDIRECT_URL
                        }else{
                            console.log(res.order)
                        }

                        // this.renderFromArgs(order)
                        //     .then(() => this.stopUpdating())
                        //     .catch(reason => {
                        //         this.stopUpdating()
                        //         reject(reason)
                        //     })
                    })
                }, this.delayBeforeUpdate)
            })
        }

        getFromStorage(key = this.options.storageKey){
            try{
                return JSON.parse(this.storage.getItem(key))
            }catch (e){
                return null
            }
        }

        saveToStorage(data, key = this.options.storageKey){
            this.storage.setItem(key, JSON.stringify(data))
        }

        canUpdate(){
            return !this.isUpdating
        }

        startUpdating(){
            this.isUpdating = true
            clearTimeout(this.updateIndicationIn)
            clearTimeout(this.updateIndicationOut)
            this.updateIndicationIn = setTimeout(() => {
                if(this.options.preloader && !this.isFocusedOn(this.rootElement)){
                    this.options.preloader.style.display = ''
                }
            }, 500)
        }

        stopUpdating(){
            this.isUpdating = false
            clearTimeout(this.updateIndicationIn)
            clearTimeout(this.updateIndicationOut)
            this.updateIndicationOut = setTimeout(() => {
                if(this.options.preloader){
                    this.options.preloader.style.display = 'none'
                }
            }, 500)
        }


        selectCountry(countryCode){
            if(this.countrySelect !== countryCode && this.countrySelect + '' !== countryCode + ''){
                this.countrySelect.value = countryCode
                this.currentCountry = countryCode
                this.getParts('indicator.country-name').forEach(indicator => {
                    indicator.innerText = this.countries[countryCode] ?? ''
                })
                if(this.cityHiddenField) this.cityHiddenField.value = ''
                if(this.cityField) this.cityField.value = ''
                if(this.cityDadataField) this.cityDadataField.value = ''
                this.saveToStorage('', 'checkoutSelectedCityName')
                if(this.dadataInputs && typeof this.dadataInputs === 'object'){
                    Object.keys(this.dadataInputs).forEach(key => {
                        const input = this.dadataInputs[key]
                        if(input){
                            input.value = ''
                        }
                    })
                }
                this.saveToStorage(countryCode, 'checkoutSelectedCountryCode')
                IPOLapiship_pvz.pvzId = false
                IPOLapiship_pvz.pvzAdress = false
                
                this.update()
            }
        }

        getCountryFromStorage(){
            const countryCode = this.getFromStorage('checkoutSelectedCountryCode')
            return (typeof countryCode === 'string' && countryCode) ? countryCode : null
        }

        selectDelivery(deliveryId){
            this.saveToStorage(deliveryId ? (deliveryId*1) : null, 'checkoutCurrentDeliveryID')
            this.currentDelivery = deliveryId
            console.log('select delivery', deliveryId)
        }
        /**
         * @returns {number|null}
         */
        getSelectedDelivery(defaultDelivery = null){
            var deliveryID = this.getFromStorage('checkoutCurrentDeliveryID')
            var cityInputVal = $(".suggestions-input").val();
            
            if(!deliveryID){
                deliveryID = 25;
            }         
            
            if(!this.args['DELIVERY'] || !this.args['DELIVERY'][deliveryID + '']){
                if(cityInputVal){
                    $(".selectedCityError").show();
                    $(".payment-data").hide();
                    $(".checkout-delivery-information").hide();
                    $(".button-checkout").addClass('disabled');
                    $(".suggestions-input").css('border-color','red');
                }else{
                    $(".selectedCityError").hide();
                    $(".payment-data").hide();
                    $(".button-checkout").addClass('disabled');    
                }
                
                return defaultDelivery
            }else{
                $(".selectedCityError").hide();
                $(".payment-data").show();
                $(".checkout-delivery-information").show();
                $(".button-checkout").removeClass('disabled');
                $(".suggestions-input").css('border-color','#D3E0EA');
            }
            
            return deliveryID * 1
        }

        getPart(dataCheckoutAttr, element = null){
            if(!element){
                element = this.rootElement
            }
            return element.querySelector('[data-checkout="' + dataCheckoutAttr + '"]')
        }
        getParts(dataCheckoutAttr, element = null){
            if(!element){
                element = this.rootElement
            }
            return element.querySelectorAll('[data-checkout="' + dataCheckoutAttr + '"]')
        }

        getPersonalFields(){
            return this.getParts('form.personal.field', this.personal)
        }

        getAddressFields(){
            return this.getParts('form.delivery-address.address.field')
        }

        getDadataFields(){
            return this.getParts('dadata.field')
        }

        getLocationProps(deliveryProps){
            let city = null
            let address = []
            let dadataLocation = null
            let branch = null
            let apishipPickup = null

            deliveryProps.forEach(prop => {
                if(prop['TYPE'] === 'LOCATION'){
                    city = prop
                }else if(prop['NAME'] === Checkout.PROP_DADATA_LOCATION) {
                    dadataLocation = prop
                }else if(prop['CODE'] === this.options['branchPropCode']){
                    branch = prop
                }else if(prop['CODE'] === 'ADDRESS'){
                    apishipPickup = prop
                }else{
                    address.push(prop)
                }
            })
            return {city, address, dadataLocation, branch, apishipPickup}
        }

        getDadataProps(){
            const {DADATA_LOCATION, DADATA_FIAS, DADATA_ZIP} = this.args['DADATA_PROPS'] ?? {}
            return {
                location: DADATA_LOCATION,
                fias: DADATA_FIAS,
                zip: DADATA_ZIP
            }
        }

        isFocusedOn(element){
            if(!element){
                return false
            }

            let elements = []
            if(typeof element === 'string'){
                elements = this.getParts(element)
            }else if(element.length !== undefined){
                elements = element
            }else{
                elements = [element]
            }
            if(elements.length === 0){
                return false
            }

            const focusedElement = document.querySelector(':focus')
            if(!focusedElement){
                return false
            }

            for(let i = 0; i < elements.length; i++){
                let parent = focusedElement
                while(parent){
                    if(parent === elements[i]){
                        return true
                    }
                    parent = parent.parentElement
                }
            }
            return false
        }

        isFocusedOnPersonal(){
            return this.isFocusedOn('form.personal')
        }

        isPodeliPaymentSelected(){
            if((typeof this.args['PAY_SYSTEM']) !== 'object'){
                return false
            }
            let isPodeli = false
            this.args['PAY_SYSTEM'].forEach(paySystem => {
                if(paySystem['CHECKED'] !== 'Y'){
                    return
                }
                if(paySystem['NAME'].toLowerCase().indexOf('podeli') !== -1){
                    isPodeli = true
                }else if(paySystem['NAME'].toLowerCase().indexOf('подели') !== -1){
                    isPodeli = true
                }
            })
            return isPodeli
        }

        getRegionCodeByCityName(cityName){
            return new Promise(resolve => {
                if(typeof cityName !== 'string'){
                    return resolve(null)
                }

                const results = []

                const request = (page) => {
                    return new Promise(resolve1 => {
                        const data = new FormData
                        data.append('select[1]', 'CODE')
                        data.append('select[2]', 'TYPE_ID')
                        data.append('select[3]', 'PARENT_ID')
                        data.append('select[VALUE]', 'ID')
                        data.append('select[DISPLAY]', 'NAME.NAME')
                        data.append('additionals[1]', 'PATH')
                        data.append('filter[=PHRASE]', cityName.trim())
                        data.append('filter[=NAME.LANGUAGE_ID]', 'ru')
                        data.append('filter[=PARENTS.ID]', this.currentCountry*1 + '')
                        data.append('version', '2')
                        data.append('PAGE_SIZE', '20')
                        data.append('PAGE', page + '')
                        
                        BX.ajax({
                            method: 'POST',
                            data: data,
                            dataType: 'json',
                            processData: false,
                            preparePost: false,
                            url: '/local/components/belleyou/sale.order.ajax/get_locations.php',
                            onsuccess: (result) => {
                                try{
                                    result = JSON.parse(result)
                                    results.push(...result.data.ITEMS)
                                }catch (e){
                                    console.error(e)
                                }
                                resolve1()
                            },
                            onfailure: () => {
                                resolve1()
                            },
                        })
                    })
                }

                const queue = [
                    request(0),
                    //request(1),
                ]
                
                Promise.all(queue).then(() => {
                    console.log('results', results)
                    const first = results.shift()
                    if(first && first['CODE']){
                        return resolve(first['CODE'])
                    }
                    resolve(null)
                })
                setTimeout(() => resolve(null), 10000)
            })
        }


        hash(data){
            const type = typeof data
            try{
                if(type === 'string'){
                    return data
                }else if(!data){
                    return (data === 0) ? '0' : ''
                }else if(type === 'object' && data.length !== undefined){
                    return data.map(datum => this.hash(datum)).join('~~~')
                }else if(type === 'object'){
                    return Object.keys(data).sort().map(key => {
                        return '~~' + key + '~~' + this.hash(data[key])
                    }).join('~~~')
                }else if(type === 'number' || type === 'bigint' || type === 'symbol'){
                    return data + ''
                }else if(type === 'boolean'){
                    return data ? 'TRUE' : 'FALSE'
                }
            }catch (e){
                try{
                    return JSON.stringify(data)
                }catch (e){
                    return Math.random() + ''
                }
            }
        }

        getAddressModeByDelivery(deliveryId) {
            if(!('addressFields' in this.options)){
                return null
            }

            const mode = this.options.addressFields[deliveryId + ''] ?? null

            if(mode === Checkout.ADDRESS_MODE_DELIVERY)
                return Checkout.ADDRESS_MODE_DELIVERY
            if(mode === Checkout.ADDRESS_MODE_PVZ)
                return Checkout.ADDRESS_MODE_PVZ
            if(mode === Checkout.ADDRESS_MODE_BRANCH)
                return Checkout.ADDRESS_MODE_BRANCH
            if(mode === Checkout.ADDRESS_MODE_NO_DELIVERY)
                return Checkout.ADDRESS_MODE_NO_DELIVERY

            return Checkout.ADDRESS_MODE_NO_DELIVERY
        }

        validate(){
            let success = true
            this.getPersonalFields().forEach(field => {
                let value = field.value
                if(typeof value !== 'string'){
                    value = value ? (value + '').trim() : null
                }else{
                    value = value.trim()
                }

                let isValid = true
                let isCorrect = true
                if(field.required && (!value || !value.length)){
                    isValid = false
                    isCorrect = false
                }else if(field.maskedInput && !field.maskedInput.checkValue()){
                    isCorrect = false
                    isValid = !field.required
                }else if(field.type === 'email' && typeof field.checkValidity === 'function' && !field.checkValidity()){
                    isCorrect = false
                    isValid = !field.required
                }

                if(isValid && isCorrect){
                    field.classList.remove(Checkout.CLASS_ERROR_INPUT)
                }else{
                    field.classList.add(Checkout.CLASS_ERROR_INPUT)
                }

                const mode = this.getAddressModeByDelivery(this.getSelectedDelivery())
                if(mode === Checkout.ADDRESS_MODE_PVZ){
                    if(!IPOLapiship_pvz.pvzId || !IPOLapiship_pvz.pvzAdress){
                        isCorrect = false
                        isValid = false
                    }
                }

                if($(".payment-data").is(':hidden')){
                    success = false    
                }
                
                if(!isValid){
                    success = false
                }
            })
            
            const mode = this.getAddressModeByDelivery(this.getSelectedDelivery())
            if(mode === "delivery"){            
                this.getAddressFields().forEach(field => {
                    let value = field.value
                    if(typeof value !== 'string'){
                        value = value ? (value + '').trim() : null
                    }else{
                        value = value.trim()
                    }

                    let isValid = true
                    let isCorrect = true
                    if(field.required && (!value || !value.length)){
                        isValid = false
                        isCorrect = false
                    }else if(field.maskedInput && !field.maskedInput.checkValue()){
                        isCorrect = false
                        isValid = !field.required
                    }

                    if(isValid && isCorrect){
                        field.classList.remove(Checkout.CLASS_ERROR_INPUT)
                    }else{
                        field.classList.add(Checkout.CLASS_ERROR_INPUT)
                    }               
                    
                    if(!isValid){
                        success = false
                    }
                })
            }
            
            const button = this.getPart('checkout-button')
            if(button){
                if(success){
                    button.classList.remove('disabled')
                }else{
                    button.classList.add('disabled')
                }
            }
            return success
        }

        analyzeCart(){
            const info = {
                hasCertificates: false,
                hasRegularProducts: false,
            }
            try{
                const certificate_ids = this.options.certificate_ids
                if(certificate_ids.length){
                    const items = this.args['GRID']['ROWS'] ?? {}
                    console.log('products in cart', items)
                    Object.keys(items).forEach(key => {
                        const product = items[key].data ?? null
                        if (!product) {
                            return
                        }

                        const id = product['PRODUCT_ID'] * 1
                        const isCertificate = certificate_ids.indexOf(id) !== -1
                        if(isCertificate){
                            info.hasCertificates = true
                            console.log('product', id, 'is certificate')
                        }else{
                            info.hasRegularProducts = true
                            console.log('product', id, 'is not certificate')
                        }
                    })
                }
            }catch (e){
                console.error(e)
            }
            info.onlyCertificates = info.hasCertificates && !info.hasRegularProducts
            console.log(info)
            return info
        }

        maybeClearCache(userHash){
            try{
                let old = this.getFromStorage('CHECKOUT_LAST_USER_HASH')
                old = '' + (old ? old : '')
                if(old !== (userHash ? userHash+'' : '')){
                    const toRemove = [
                        this.options.storageKey,
                        'apishipPvzId',
                        'checkoutApishipFields',
                        'checkoutSelectedCityName',
                        'checkoutCurrentBranch',
                        'checkoutSelectedCountryCode',
                        'checkoutCurrentDeliveryID',
                        'checkoutCurrentAddress',
                    ]

                    toRemove.forEach(key => {
                        try{
                            if(key){
                                this.storage.removeItem(key)
                            }
                        }catch (e){}
                    })
                }
            }catch (e){
                console.log('unable to clear checkout cache', e)
            }
            this.saveToStorage(userHash, 'CHECKOUT_LAST_USER_HASH')
        }

        saveCurrentAddress(){
            this.saveToStorage(this.currentAddress, 'checkoutCurrentAddress')
        }

        loadCurrentAddress(){
            const address = this.getFromStorage('checkoutCurrentAddress')
            this.currentAddress = (typeof address === 'object' && address) ? address : {}
        }

    }
})();