
import General from '../General.js';

const { createApp } = Vue;

const DeviceForm = createApp({
    data() {
        return {
            catalog: {
                devices: [],
                components: {}
            },
            form: {
                device_id: '',
                custom_name: '',
                characteristics: []
            },
            loading: true,
            saving: false,
            error: null
        }
    },
    computed: {
        isCustomizable() {
            if (!this.form.device_id) return false;
            const device = this.catalog.devices.find(d => d.id === this.form.device_id);
            if (!device) return false;

            const customizableTypes = ['PC', 'Laptop', 'Mac', 'Steam Deck', 'ROG Ally', 'Linux Handheld', 'Android', 'Smart TV'];
            return customizableTypes.includes(device.name);
        }
    },
    watch: {
        'form.device_id': function (newVal, oldVal) {
            if (newVal != oldVal && !this.isCustomizable) {
                this.form.characteristics = [];
            }
        }
    },
    async mounted() {
        await this.loadCatalog();
        if (window.DEVICE_ID) {
            this.loadDevice(window.DEVICE_ID);
        }
    },
    methods: {
        async loadDevice(id) {
            this.loading = true;
            try {
                const response = await General.get(`/devices/${id}`);
                const device = response.device;

                this.form.device_id = device.device_id;
                this.form.custom_name = device.custom_name;

                if (device.characteristics && device.characteristics.length > 0) {
                    this.form.characteristics = device.characteristics.map(c => ({
                        key: c.key,
                        pc_component_id: c.pc_component_id || '',
                        value: c.value || ''
                    }));
                }
            } catch (err) {
                console.error(err);
                this.error = 'Error cargando dispositivo.';
            } finally {
                this.loading = false;
            }
        },
        async loadCatalog() {
            this.loading = true;
            try {
                const response = await General.get('/catalog');
                this.catalog.devices = response.devices;
                this.catalog.components = response.components;
            } catch (err) {
                console.error(err);
                this.error = 'Error cargando catálogo.';
            } finally {
                this.loading = false;
            }
        },
        addCharacteristic() {
            this.form.characteristics.push({
                key: '',
                pc_component_id: '',
                value: ''
            });
        },
        removeCharacteristic(index) {
            this.form.characteristics.splice(index, 1);
        },
        async save() {
            this.saving = true;
            this.error = null;
            try {
                // Prepare payload
                const payload = {
                    device_id: this.form.device_id,
                    custom_name: this.form.custom_name,
                    characteristics: this.form.characteristics.map(c => ({
                        key: c.key,
                        pc_component_id: c.pc_component_id || null, // Ensure null if empty
                        value: c.value
                    }))
                };

                if (window.DEVICE_ID) {
                    await General.put(`/devices/${window.DEVICE_ID}`, payload);
                } else {
                    await General.post('/devices', payload);
                }

                // Redirect on success
                window.location.href = '/devices';
            } catch (err) {
                console.error(err);
                if (err.errors) {
                    // Laravel validation errors
                    this.error = Object.values(err.errors).flat().join('<br>');
                } else {
                    this.error = err.message || 'Error al guardar dispositivo.';
                }
            } finally {
                this.saving = false;
            }
        }
    }
});

DeviceForm.mount("#device-form");
