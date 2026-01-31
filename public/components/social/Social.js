
import General from '../General.js';

const { createApp } = Vue;

const Social = createApp({
    data() {
        return {
            profile: null,
            loading: true,
            error: null
        }
    },
    async mounted() {

    },
    methods: {

    }
});

Social.mount("#social-dashboard");
