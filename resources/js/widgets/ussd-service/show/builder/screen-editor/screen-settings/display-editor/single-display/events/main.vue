<template>

    <!-- Screen Display Events -->
    <div>

        <!-- Display Event Explainer -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Events</span> to Create, Read, Update and Delete content using
            <span class="font-italic text-success font-weight-bold">CRUD API's</span> and
            <span class="font-italic text-success font-weight-bold">Validate</span>, 
            <span class="font-italic text-success font-weight-bold">Format</span> or 
            <span class="font-italic text-success font-weight-bold">Store</span> 
            content including the users input among other dynamic content.
        </Alert>

        <!-- Before Display Response Events -->
        <div class="mb-2">

            <span class="d-block font-weight-bold text-dark">Before (User Reply)</span>

            <div class="bg-grey-light border mt-2 mb-3 pt-3 px-2 pb-2">
                
                <eventsManager :events="display.content.events.before_reply" :screen="screen" :builder="builder"></eventsManager>

            </div>

        </div>

        <!-- After Display Response Events -->
        <div class="mb-2">

            <span class="d-block font-weight-bold text-dark">After (User Reply)</span>

            <div class="bg-grey-light border mt-2 mb-3 pt-3 px-2 pb-2">
                
                <eventsManager :events="display.content.events.after_reply" :screen="screen" :builder="builder"></eventsManager>

            </div>

        </div>

    </div>
    
</template>

<script>

    //  Get the events manager
    import eventsManager from './../../../../events/main.vue';

    export default {
        props: { 
            screen: {
                type: Object,
                default:() => {}
            },
            display: {
                type: Object,
                default:() => {}
            },
            builder: {
                type: Object,
                default: () => {}
            }
        },
        components: {
            eventsManager
        },
        data(){
            return {
                
            }
        }
    };
  
</script>