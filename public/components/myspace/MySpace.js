
import General from '../General.js';

const { createApp } = Vue;

const MySpace = createApp({
    data() {
        return {
            profile: null,
            devices: [],
            loading: true,
            error: null
        }
    },
    async mounted() {
        await this.loadData();
    },
    methods: {
        async loadData() {
            this.loading = true;
            try {
                // Fetch profile
                const profileRes = await General.get('/profile');
                this.profile = profileRes.profile;

                // Fetch devices
                const devicesRes = await General.get('/devices');
                this.devices = devicesRes.devices;

            } catch (err) {
                console.error(err);
                if (err.status === 401 || err.status === 403) {
                    window.location.href = '/login';
                }
                this.error = 'Error cargando datos';
            } finally {
                this.loading = false;
            }
        },
        async logout() {
            try {
                await General.post('/logout');
                localStorage.removeItem('token');
                window.location.href = '/login';
            } catch (err) {
                console.error(err);
            }
        }
    }
});

MySpace.mount("#myspace");
