<template>
    <div>
        <!-- Modal -->
        <mainModal 
            v-bind="$props" 
            :width="650"
            :isSaving="isSending" 
            :hideModal="hideModal"
            title="Send Quotation"
            :okText="(stage == 1) ? '' : okText" 
            :cancelText="(stage == 1) ? '' : 'Cancel'"
            @on-ok="send()" 
            @visibility="$emit('visibility', $event)">

            <template slot="content">

                <deliveryWidget 
                    :deliveryMethods="deliveryMethods"
                    :clientDetails="localQuotation.customized_customer_details"
                    :deliveryPhones="localDeliveryPhones"
                    :deliveryMailAddress="localDeliveryMailAddress"
                    :deliveryMailSubject="localDeliveryMailSubject"
                    :deliveryMailMessage="localDeliveryMailMessage"
                    :deliverySmsMessage="smsMessageCompiled"
                    :testSmsUrl="'/api/quotations/'+localQuotation.id+'/send?test=1'"
                    :testEmailUrl="'/api/quotations/'+localQuotation.id+'/send?test=1'"
                    :shortcodes="shortcodes"
                    :showSmsPhoneImg="false"
                    @updated:stage="stage = $event"
                    @updated:deliveryMethods="deliveryMethods = $event"
                    @updated:deliveryPhones="localDeliveryPhones = $event"
                    @updated:deliveryMailAddress="localDeliveryMailAddress = $event"
                    @updated:deliveryMailSubject="localDeliveryMailSubject = $event"
                    @updated:deliveryMailMessage="localDeliveryMailMessage = $event">
                </deliveryWidget>

            </template>
            
        </mainModal>    
    </div>
</template>
<script>

    import moment from 'moment';
    import mainModal from './main.vue';
    import froalaEditor from './../wiziwigEditors/froalaEditor.vue'; 
    import Loader from './../loaders/Loader.vue';
    import deliveryWidget from './../../../widgets/delivery/show/main.vue';

    export default {
        props:{
            quotation: {
                type: Object,
                default: null
            },
            subject: {
                type: String,
                default: ''
            },
            email: {
                type: String,
                default: ''
            },
            message: {
                type: String,
                default: ''
            }
        },
        components: { mainModal, froalaEditor, Loader, deliveryWidget },
        data(){
            return{
                moment: moment,
                
                localQuotation: _.cloneDeep(this.quotation),
                deliveryMethods: ['Sms' /*, Email */],
                localDeliveryPhones: [],
                localDeliveryMailSubject: this.subject || 'Quotation #'+this.quotation['reference_no_value'],
                localDeliveryMailAddress: this.quotation.customized_customer_details.email,
                localDeliveryMailMessage: this.emailMsg(),
                shortcodes: this.getShotCodes(),
                
                hideModal: false,
                isLoading: false,
                isSending: false,
                stage: 1
            }
        },
        computed: {
            smsMessageCompiled: function(){

                var referenceNo = this.quotation.reference_no_value;
                var items = '';
                var currency = (((this.quotation || {}).currency_type || {}).currency || {}).symbol || '';
                var grand_total = this.formatPrice( (this.quotation.grand_total || 0), currency);
                var expiry_date = moment(this.quotation.expiry_date).format('MMM DD YYYY');
                var client = ((this.quotation || {}).customized_customer_details || {});
                var company = ((this.quotation || {}).customized_company_details || {});

                for( var x = 0; x < this.quotation.items.length; x++  ){
                    x == 0 ? items += '' : items +=' ';
                    items += ( (this.quotation.items[x].quantity) +'x '+(this.quotation.items[x].name) );
                }

                var characterLimit = 160;
                //  Company info text limit = 23
                var companyName = this.truncate(company.name.trim(), 21) + ( company.name.length <= 21 ? ':' : '' );       //  Optimum Quality: 
                //  Reference text limit = 16
                var reference = 'Quotation #'+referenceNo;                        //  Quotation #002
                //  Amount text limit = 20
                var amount = 'Amount ' + grand_total;                           //  Amount P350.00
                //  Due date text limit = 21
                var dueDate = ' due '+expiry_date;                              //  due on 15 Feb 2018
                //  Reply for payment text limit = 32
                var replyWith = '.Reply with '+referenceNo+'#<pin> to pay';     //  Reply with 002#<pin> to pay
                
                //  items text limit = 49
                var charLeft = (characterLimit - (companyName+reference+amount+dueDate+replyWith).length);
                var items = this.truncate(' for ' + items + ( items.length <= charLeft ? '.' : '' ) , charLeft);    //  for 1x Basic Website, 1x Web Hosting, 5x Emails. 

                var message = companyName+reference+items+amount+dueDate+replyWith;

                //  Return the compiled message
                return message;
            },
            okText: function(){
                var smsText = this.deliveryMethods.includes('Sms') ? 'Sms': '';
                var emailText = this.deliveryMethods.includes('Email') ? 'Email': '';
                var andText = this.deliveryMethods.includes('Sms') && this.deliveryMethods.includes('Email') ? ' & ': '';
                
                return 'Send '+( smsText )+( andText )+( emailText );
            }
        },
        methods: {
            emailMsg(){
                
                var money = this.quotation.grand_total || 0;
                var currency = (((this.quotation || {}).currency_type || {}).currency || {}).symbol || '';
                var amount = this.formatPrice(money, currency);
                var expiry_date = moment(this.quotation.expiry_date).format('MMM DD YYYY');
                var company_name = this.quotation.customized_company_details.name;

                return '<p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:12px;font-family:arial, \'helvetica neue\', helvetica, sans-serif;line-height:18px;color:#000000;">  \
                            Good day,  \
                        </p> \
                        <br> \
                        <p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:12px;font-family:arial, \'helvetica neue\', helvetica, sans-serif;line-height:18px;color:#000000;">  \
                            Please find attached <strong>Quotation #'+this.quotation['reference_no_value']+'</strong> \
                            created on your account for requested services valued at  \
                            <strong>'+amount+' </strong> \
                            and valid till  \
                            <strong>'+expiry_date+'</strong>  \
                            or earlier. \
                        </p> \
                        <br> \
                        <p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:12px;font-family:arial, \'helvetica neue\', helvetica, sans-serif;line-height:18px;color:#000000;">  \
                            We look forward to conducting future business with you. \
                        </p> \
                        <br> \
                        <p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:12px;font-family:arial, \'helvetica neue\', helvetica, sans-serif;line-height:18px;color:#000000;">  \
                            Regards, \
                            <br> \
                            '+company_name+' \
                        </p>';
            },
            formatPrice(money, symbol) {
                let val = (money/1).toFixed(2).replace(',', '.');
                return (symbol ? symbol : '') + val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            },
            truncate(string, limit){
                return (string.length > limit) ? string.substring(0, limit - 3)+'...' : string;
            },
            getShotCodes(){
                
                var money = this.quotation.grand_total || 0;
                var currency = (((this.quotation || {}).currency_type || {}).currency || {}).symbol || '';
                var sub_total = this.formatPrice( (this.quotation.sub_total_value || 0), currency);
                var grand_total = this.formatPrice( (this.quotation.grand_total || 0), currency);
                var client = ((this.quotation || {}).customized_customer_details || {});
                var company = ((this.quotation || {}).customized_company_details || {});

                var shortcodes = {
                    '[quotation_heading]': this.quotation.heading,
                    '[quotation_reference_no]': '#'+this.quotation.reference_no_value,
                    '[created_date]': moment(this.quotation.created_date).format('MMM DD YYYY'),
                    '[expiry_date]': moment(this.quotation.expiry_date).format('MMM DD YYYY'),
                    '[sub_total]': sub_total,
                    '[grand_total]': grand_total,
                    '[currency]': currency,
                    '[client_company_name]': client.name,
                    '[client_first_name]': client.first_name,
                    '[client_last_name]': client.last_name,
                    '[client_full_name]': client.full_name,
                    '[client_email]': client.email,
                    '[my_company_name]': company.name,
                    '[my_company_email]': company.email,
                };

                if( client.model_type == 'user' ){
                    delete shortcodes['[client_company_name]']; 
                }else if( client.model_type == 'company' ){
                    delete shortcodes['[client_first_name]'];                     
                    delete shortcodes['[client_last_name]'];                     
                    delete shortcodes['[client_full_name]'];                        
                }
                console.log('shortcodes');
                console.log('shortcodes');
                console.log('shortcodes');
                console.log('shortcodes');
                console.log('shortcodes');
                console.log(shortcodes);
                return shortcodes;
            },
            send(){

                var self = this;

                //  Start loader
                self.isSending = true;

                var data = {
                        sms: {
                            phones: this.localDeliveryPhones,
                            message: this.smsMessageCompiled,
                        },
                        mail: {
                            primaryEmails: [
                                this.localDeliveryMailAddress
                            ],
                            ccEmails: [],
                            bccEmails: [],
                            subject: this.localDeliveryMailSubject,
                            message: this.localDeliveryMailMessage
                        },
                        deliveryMethods: this.deliveryMethods
                    }

                console.log('Attempt to send quotation data...');
                console.log(data);

                //  Use the api call() function located in resources/js/api.js
                api.call('post', '/api/quotations/'+self.localQuotation.id+'/send', data)
                    .then(({ data }) => {

                        console.log(data);

                        //  Stop loader
                        self.isSending = false;
                        
                        //  Alert parent and pass updated quotation data
                        //  NOTE that "data = updated quotation"
                        self.$emit('sent', data);

                        //  Alert creation success
                        self.$Message.success('Quotation sent sucessfully!');

                        //  Close the modal
                        self.closeModal();

                    })         
                    .catch(response => { 
                        //  Stop loader
                        self.isSending = false;

                        console.log('sendQuotationModal.vue - Error sending quotation...');
                        console.log(response);
                    });
            },
            closeModal(){
                this.hideModal = true;
            }
        }
    }
</script>