<style scoped>

    .checkmark__circle {
        stroke-dasharray: 166;
        stroke-dashoffset: 166;
        stroke-width: 2;
        stroke-miterlimit: 10;
        stroke: #7ac142;
        fill: none;
        animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }

    .checkmark {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: block;
        stroke-width: 2;
        stroke: #fff;
        stroke-miterlimit: 10;
        float: right;
        margin: 0;
        box-shadow: inset 0px 0px 0px #7ac142;
        animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
    }

    .checkmark__check {
        transform-origin: 50% 50%;
        stroke-dasharray: 48;
        stroke-dashoffset: 48;
        animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }

    @keyframes stroke {
        100% {
            stroke-dashoffset: 0;
        }
    }
    @keyframes scale {
        0%, 100% {
            transform: none;
        }
        50% {
            transform: scale3d(1.1, 1.1, 1);
        }
    }
    @keyframes fill {
        100% {
            box-shadow: inset 0px 0px 0px 30px #7ac142;
        }
    }

    .checkmark__circle_fast {
        animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) forwards !important;
    }

    .checkmark_fast {
        animation: fill 0.25s ease-in-out 0s forwards, scale 0.2s ease-in-out 0.2s both !important;
    }

    .checkmark__check_fast {
        animation: stroke 0.1s cubic-bezier(0.65, 0, 0.45, 1) 0.2s forwards !important;
    }

</style>

<template>

    <!-- SVG ICON -->
    <svg :class="'checkmark' + (speed ? ' checkmark_fast' : '')" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
        <circle :class="'checkmark__circle_fast' + (speed ? ' checkmark__circle_fast' : '')" cx="26" cy="26" r="25" fill="none"/>
        <path :class="'checkmark__check' + (speed ? ' checkmark__check_fast' : '')" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
    </svg>

</template>

<script type="text/javascript">

    export default {
        props: {
            speed: {
                type: Boolean,
                default: false
            }
        }
    }

</script>
