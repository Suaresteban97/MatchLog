
import General from '../General.js';

const { createApp } = Vue;

const DeviceList = createApp({
    data() {
        return {
            devices: [],
            loading: true,
            error: null
        }
    },
    async mounted() {
        await this.loadDevices();
    },
    methods: {
        async loadDevices() {
            this.loading = true;
            try {
                const response = await General.get('/devices');
                this.devices = response.devices;
            } catch (err) {
                console.error(err);
                if (err.status === 401 || err.status === 403) {
                    window.location.href = '/login';
                }
                this.error = 'Error cargando dispositivos.';
            } finally {
                this.loading = false;
            }
        },
        async deleteDevice(id) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "No podrás revertir esto.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        await General.delete(`/devices/${id}`);
                        Swal.fire(
                            'Eliminado!',
                            'El dispositivo ha sido eliminado.',
                            'success'
                        );
                        this.devices = this.devices.filter(d => d.id !== id);
                    } catch (err) {
                        console.error(err);
                        Swal.fire(
                            'Error',
                            'Hubo un problema al eliminar el dispositivo.',
                            'error'
                        );
                    }
                }
            })
        }
    }
});

DeviceList.mount("#device-list");
