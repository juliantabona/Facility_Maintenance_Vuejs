<template>

    <Card :style="{ width: '100%' }">
        
        <!-- Loader for when loading the chart information -->
        <Loader v-if="isLoading" :loading="isLoading" type="text" :style="{ marginTop:'40px' }">Loading stats...</Loader>

        <!-- Activity Number Card -->
        <Row v-if="!isLoading" :gutter="20">
            <Col :span="24/(overallStatLabels.length)" v-for="(item, i) in overallStatLabels" :key="i">
                
                <NumberCounterCard   :title="overallStatLabels[i]" 
                                     :isMoneyList="isMoneyList"
                                     :renameTitleList="renameTitleList"
                                     :amount="overallStatGrandTotal[i]" 
                                     :count="overallStatTotalCount[i]" 
                                     :currency="baseCurrency" :showZero="false" class="mb-2" 
                                     :route="{ path: routePath, query: { status: ( overallStatLabels[i] ), location: 'top' } }"
                                     :active="overallStatLabels[i] == $route.query.status && $route.query.location == 'top'"
                                     type="normal">
                </NumberCounterCard>
            
            </Col>
        </Row>

        <!-- Activity Number Card -->
        <Row v-if="!isLoading" :gutter="20">
            <Col :span="24/(mainStatLabels.length)" v-for="(item, i) in mainStatLabels" :key="i">
                
                <NumberCounterCard   :title="mainStatLabels[i]" 
                                     :isMoneyList="isMoneyList"
                                     :renameTitleList="renameTitleList"
                                     :amount="mainStatGrandTotalunt[i]" :count="mainStatTotalCount[i]" 
                                     :currency="baseCurrency" :showZero="false" class="mb-2" 
                                     :route="{ path: routePath, query: { status: ( mainStatLabels[i] ), location: 'bottom' } }"
                                     :active="mainStatLabels[i] == $route.query.status && $route.query.location == 'bottom'"
                                     :type="determineType(i)">
                </NumberCounterCard>
            
            </Col>
        </Row>

        <!-- Activity Number Card -->
        <Row v-if="!isLoading" :gutter="20">
            <Col :span="24/(subStatLabels.length)" v-for="(item, i) in subStatLabels" :key="i">
                
                <NumberCounterCard   :title="subStatLabels[i]" 
                                     :isMoneyList="isMoneyList"
                                     :renameTitleList="renameTitleList"
                                     :amount="mainStatGrandTotalunt[i]" :count="subStatTotalCount[i]" 
                                     :currency="baseCurrency" :showZero="false" class="mb-2" 
                                     :route="{ path: routePath, query: { status: ( subStatLabels[i] ), location: 'bottom' } }"
                                     :active="subStatLabels[i] == $route.query.status && $route.query.location == 'bottom'"
                                     :type="determineType(i)">
                </NumberCounterCard>
            
            </Col>
        </Row>


    </Card>

</template>

<script>

    /*  Loaders  */
    import Loader from './../../components/_common/loaders/Loader.vue';

    /*  Chart.js  */
    import Chart from 'chart.js';
    
    /*  Cards  */
    import NumberCounterCard from './../../components/_common/cards/NumberCounterCard.vue';

    export default {
        props:{
            url: {
                type: String,
                default: ''
            },
            routePath: {
                type: String,
                default: ''      
            },
            isMoneyList: {
                type: Array,
                default: () => { return [] }
            },
            renameTitleList: {
                type: Array,
                default: () => { return [] }
            }
        },
        data(){
            return {
                isLoading: false,
                fetchedMainStats: [],
                fetchedOverallStats: [],
                baseCurrency: null,

                //  store is a global custom class found in store.js
                //  We use it to access data accessible globally
                //  In this case we need to access the data
                //  allocationType to know whether the user
                //  wants company/branch related data
                allocationType: store.allocationType,
            }
        },
        components: { Loader, NumberCounterCard },
        computed: {
            /******************************
             *   MAIN STATS               *
             ******************************/
            mainStatLabels: function(){
                if( (this.fetchedMainStats || {}).length){
                    return this.fetchedMainStats.map(stat => stat.name);
                }

                return [];
                
            },
            mainStatTotalCount: function(){
                if( (this.fetchedMainStats || {}).length){
                    return this.fetchedMainStats.map(stat => stat.total_count);
                }

                return [];

            },
            mainStatGrandTotalunt: function(){
                if( (this.fetchedMainStats || {}).length){
                    return this.fetchedMainStats.map(stat => stat.grand_total);
                }

                return [];

            },
            /******************************
             *   SUB STATS                *
             ******************************/
            subStatLabels: function(){
                if( (this.fetchedSubStats || {}).length){
                    return this.fetchedSubStats.map(stat => stat.name);
                }

                return [];
                
            },
            subStatTotalCount: function(){
                if( (this.fetchedSubStats || {}).length){
                    return this.fetchedSubStats.map(stat => stat.total_count);
                }

                return [];

            },
            subStatGrandTotalunt: function(){
                if( (this.fetchedSubStats || {}).length){
                    return this.fetchedSubStats.map(stat => stat.grand_total);
                }

                return [];

            },
            /******************************
             *   OVERALL STATS            *
             ******************************/
            overallStatLabels: function(){
                if( (this.fetchedOverallStats || {}).length){
                    return this.fetchedOverallStats.map(stat => stat.name);
                }

                return [];
                
            },
            overallStatTotalCount: function(){
                if( (this.fetchedOverallStats || {}).length){
                    return this.fetchedOverallStats.map(stat => stat.total_count);
                }

                return [];

            },
            overallStatGrandTotal: function(){
                if( (this.fetchedOverallStats || {}).length){
                    return this.fetchedOverallStats.map(stat => stat.grand_total);
                }

                return [];

            }
        },
        methods: {
            determineType(i){
                if( this.mainStatLabels[i] == 'Paid' ){
                    return 'success';
                }else if( this.mainStatLabels[i] == 'Expired' ){
                    return 'error';
                }else if( this.mainStatLabels[i] == 'Cancelled' ){
                    return 'warning';
                }else{
                    return 'normal';
                }
            },
            fetch() {
                const self = this;

                //  Start loader
                self.isLoading = true;

                //  The allocationType: Whether to get company/branch specific data
                var allocationType = this.allocationType ? 'allocation='+this.allocationType : ''; 

                console.log('Start getting card activity statistics...');

                if( this.url ){

                    //  Use the api call() function located in resources/js/api.js
                    api.call('get', '/api'+this.url+'?'+allocationType)
                        .then(({data}) => {
                            
                            console.log(data);

                            //  Stop loader
                            self.isLoading = false;
                            
                            //  Get the data
                            self.fetchedMainStats = data['stats'];
                            self.fetchedSubStats = data['sub_stats'];
                            self.fetchedOverallStats = data['overview_stats'];
                            self.baseCurrency = data['base_currency'];

                        })         
                        .catch(response => { 

                            //  Stop loader
                            self.isLoading = false;

                            console.log('activityCardWidget.vue - Error getting card activity statistics...');
                            console.log(response);    
                        });

                }
            }
        },
        created () {

            //  Fetch the statistics
            this.fetch();

            //  Listen for global changes on the allocation type. 
            //  The reource is used to reflect which data we want to get.
            //  It may be the users company/branch specific data.

            var self = this;

            Event.$on('updatedAllocationType', function(updatedAllocationType){
                
                //  Get the updated allocationType e.g) company/branch
                self.allocationType = updatedAllocationType;

                //  Fetch the statistics
                self.fetch();
                
            });
        },
        beforeDestroy() {
            //  Stop listening for global changes on the allocation type.
            Event.$off('updatedAllocationType');
        }
    }
</script>