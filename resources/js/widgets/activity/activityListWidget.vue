<template>

    <filterableList :tableColumnsData="tableColumns" :filterableData="filterable" @generateURL=""></filterableList>

</template>
<script type="text/javascript">

    import moment from 'moment';
    import filterableList from './../../components/_common/list/filterableList.vue';

    export default {
        props:{
            modelId: {
                type: [Number, String],
                default: null
            },
            modelType: {
                type: String,
                default: null
            },
            activityType: {
                type: String,
                default: null
            }
        },
        components: { filterableList },
        data() {
            return {

                moment: moment,

                // Table columns 
                tableColumns: [
                    {
                        width: 80,
                        title: 'ID',
                        sortable: true,
                        render: (h, params) => {
                            return h('span', (params.row.id));
                        }
                    },
                    {
                        width: 150,
                        title: 'Name',
                        sortable: true,
                        render: (h, params) => {
                            return h('span', params.row.details.title );
                        }
                    },
                    {
                        width: 420,
                        title: 'Description',
                        sortable: true,
                        render: (h, params) => {
                            return h('Poptip', {
                                        style: {
                                            width: '100%'
                                        },
                                        props: {
                                            width: 350,
                                            wordWrap: true,
                                            trigger:'hover',
                                            content: params.row.details.description,
                                        }
                                    }, [
                                        h('p', {
                                                style: {
                                                    maxWidth: '320px',
                                                    whiteSpace: 'nowrap',
                                                    overflow: 'hidden',
                                                    textOverflow: 'ellipsis'
                                                }
                                            },params.row.details.description)
                                    ]);
                        }

                    },
                    {
                        width: 180,
                        title: 'Activity By',
                        sortable: true,
                        render: (h, params) => {
                            return h('a', {
                                    props: {
                                        href: '#',
                                    }
                                },
                                (params.row.created_by || {}).first_name +' '+ (params.row.created_by || {}).last_name);
                        }
                    },
                    {
                        width: 120,
                        title: 'Date',
                        sortable: true,
                        render: (h, params) => {
                            return h('span', this.formatDate(params.row.created_date));
                        }
                    },
                    {
                        width: 122,
                        title: 'Action',
                        align: 'center',
                        render: (h, params) => {
                            return h('div', [
                                h('Button', {
                                    props: {
                                        type: 'primary',
                                        size: 'small'
                                    },
                                    style: {
                                        marginRight: '5px'
                                    },
                                    on: {
                                        click: () => {
                                            this.$router.push({ name: 'show-invoice', params: { id: params.row.id } });
                                        }
                                    }
                                }, 'View')
                            ]);
                        }
                    }
                ],

                // Filterable data
                filterable: {
                    url: null,
                    orderables: [
                        {title: 'Id', name: 'id'},                      
                        {title: 'Created At', name: 'created_at'},
                    ],
                    filterGroups: [
                        {
                            name: 'Invoice',
                            filters: [
                                {title: 'Id', name: 'id', type: 'numeric'},
                                {title: 'Published Date', name: 'created_at', type: 'datetime'},
                            ]
                        },
                        {
                            name: '- With Staff',
                            filters: [
                                {title: 'Id', name: 'client.id', type: 'numeric'},
                                {title: 'Created At', name: 'client.created_at', type: 'datetime'},
                            ]
                        }
                    ]
                }
            }
        },
        methods: {
            generateURL: function () {

                //  Additional data to eager load along with each invoice activity found
                var connections = '';
                
                var modelId = this.modelId ? 'modelId='+this.modelId : '';
                var modelType = this.modelType ? '&modelType='+this.modelType : '';
                var type = this.activityType ? '&type='+this.activityType : '';

                //  Url generated for the filterable Api call  
                var url = '/api/recentactivities?'+ modelId + modelType + type + connections;

                //  Assign url to the filterable object
                this.filterable.url = url;
            },
            formatDate(date) {
                return this.moment(date).format('MMM DD YYYY');
            }
        },
        created () {
            this.generateURL();
        }
    }
</script>
