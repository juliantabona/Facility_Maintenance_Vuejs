<style scoped>

    /*  Field Toolbox */

    .section-field >>> .field-toolbox{
        opacity:0;
        position: absolute;
        top: -20px;
        right: 0px;
        background: #f3f3f3;
        border-radius: 20px 0 0 20px;
        z-index: 1;
    }

    .section-field:hover >>> .field-toolbox{
        opacity:1;
    }

    /*  Field el-badge  */
    .el-badge >>> sup{
        top:0;
        left: 3px;
    }

    .section-field >>> .field-toolbox .field-icon{
        padding: 2px;
        border-radius: 100%;
        color: black;
        cursor: pointer;
    }

    .section-field >>> .field-toolbox .field-icon:hover{
        color: #ffffff;
        background: gold;
    }

</style>

<template>

    <Col v-if="field" :span="field.width" class="section-field pb-2 pt-1 pr-2 pl-2 mb-1 mt-1">
        
        <div v-if="showToolBar" class="field-toolbox float-right d-block">
            <span class="mr-5">
                <el-badge :value="field.width+'/24'" class="item" type="primary"></el-badge>
            </span>
            <Icon type="ios-move" class="field-icon field-dragger mr-2" size="17" />
            <Poptip
                confirm
                title="Are you sure you want to delete this field?"
                ok-text="Yes"
                cancel-text="No"
                @on-ok="$emit('removeField')">
                <Icon type="ios-trash-outline" class="field-icon" size="18"/>
            </Poptip>
            <Icon type="ios-create-outline" class="field-icon" size="18" @click="$emit('showFieldEditDrawer', field)" />
            <i class="field-icon el-icon-d-arrow-left" @click="minimizeField(field, 12)"></i>
            <i class="field-icon el-icon-arrow-left" @click="minimizeField(field, 4)"></i>
            <i class="field-icon el-icon-arrow-right" @click="maximizeField(field, 4)"></i>
            <i class="field-icon el-icon-d-arrow-right" @click="maximizeField(field, 12)"></i>
            
        </div>

        <div class="section-content-box">
            <oq-Template-Field-Mockup :field="field" :show-settings="false"></oq-Template-Field-Mockup>
        </div>
    
    </Col>

</template>

<script>
    import draggable from 'vuedraggable';
    export default {
        props:{
            field: {
                type: Object,
                default: null
            },
            section: {
                type: Object,
                default: null
            },
            showToolBar: {
                type: Boolean,
                default: true
            }
        },
        components: {
            draggable
        },
        data(){
            return {
                
            }
        },
        methods: {
            minimizeField(field, factor){

                if(field.width - factor > 0){
                    field.width -= factor;
                }

            },
            maximizeField(field, factor){

                if(field.width + factor <= 24){
                    field.width += factor;
                }else if(field.width + factor/2 <= 24){
                    field.width += factor/2;
                }else if(field.width + factor/4 <= 24){
                    field.width += factor/4;
                }
            }
        }
    }
</script>