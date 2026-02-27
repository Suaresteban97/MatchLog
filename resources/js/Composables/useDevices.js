import { ref, reactive, computed } from 'vue';
import { useApi } from './useApi';
import { router } from '@inertiajs/vue3';

export function useDevices() {
    const { get, post, put, del, error, loading } = useApi();

    // Estado para la lista (DeviceList)
    const devices = ref([]);

    // Estado para el formulario (DeviceForm)
    const catalog = reactive({
        types: [
            'cpu',
            'gpu',
            'ram',
            'storage',
            'motherboard',
            'psu',
            'case',
            'cooler',
            'monitor',
            'keyboard',
            'mouse',
            'headset',
            'controller',
            'other'
        ],
        devices: [],
        components: {}
    });

    const form = reactive({
        device_id: '',
        custom_name: '',
        characteristics: []
    });

    const isSaving = ref(false);

    // --- Lógica del Listado ---
    const loadDevices = async () => {
        try {
            const response = await get('/devices');
            devices.value = response.devices;
        } catch (err) {
            console.error('Error cargando dispositivos:', err);
        }
    };

    const deleteDevice = async (id) => {
        if (confirm("¿Estás seguro de eliminar este dispositivo? No podrás revertir esto.")) {
            try {
                await del(`/devices/${id}`);
                devices.value = devices.value.filter(d => d.id !== id);
                alert("Dispositivo eliminado exitosamente.");
            } catch (err) {
                console.error("Error al eliminar", err);
                alert("Hubo un problema al eliminar el dispositivo.");
            }
        }
    };

    // --- Lógica del Formulario ---
    const isCustomizable = computed(() => {
        if (!form.device_id) return false;
        const device = catalog.devices.find(d => d.id === form.device_id);
        if (!device) return false;

        const customizableTypes = ['PC', 'Laptop', 'Mac', 'Steam Deck', 'ROG Ally', 'Linux Handheld', 'Android', 'Smart TV'];
        return customizableTypes.includes(device.name);
    });

    const loadDevice = async (id) => {
        try {
            const response = await get(`/devices/${id}`);
            const device = response.device;

            form.device_id = device.device_id;
            form.custom_name = device.custom_name;

            if (device.characteristics && device.characteristics.length > 0) {
                form.characteristics = device.characteristics.map(c => ({
                    key: c.key,
                    pc_component_id: c.pc_component_id || '',
                    value: c.value || ''
                }));
            }
        } catch (err) {
            console.error('Error cargando dispositivo:', err);
        }
    };

    const loadCatalog = async () => {
        try {
            const response = await get('/catalog');
            catalog.devices = response.devices;
            catalog.components = response.components;
        } catch (err) {
            console.error('Error cargando catálogo:', err);
        }
    };

    const addCharacteristic = () => {
        form.characteristics.push({ key: '', pc_component_id: '', value: '' });
    };

    const removeCharacteristic = (index) => {
        form.characteristics.splice(index, 1);
    };

    const saveDevice = async (deviceIdToEdit = null) => {
        isSaving.value = true;
        error.value = null;
        try {
            const payload = {
                device_id: form.device_id,
                custom_name: form.custom_name,
                characteristics: form.characteristics.map(c => ({
                    key: c.key,
                    pc_component_id: c.pc_component_id || null,
                    value: c.value
                }))
            };

            if (deviceIdToEdit) {
                await put(`/devices/${deviceIdToEdit}`, payload);
            } else {
                await post('/devices', payload);
            }

            // Redirección SPA usando Inertia
            router.visit('/devices');
        } catch (err) {
            console.error('Error guardando:', err);
            // Mapeo simple de errores Laravel si existe
            if (err.errors) {
                error.value = Object.values(err.errors).flat().join(' | ');
            } else {
                error.value = err.message || 'Error al guardar dispositivo.';
            }
        } finally {
            isSaving.value = false;
        }
    };

    return {
        // Listado
        devices,
        loadDevices,
        deleteDevice,
        // Formulario
        catalog,
        form,
        isCustomizable,
        loadDevice,
        loadCatalog,
        addCharacteristic,
        removeCharacteristic,
        saveDevice,
        // Gbl
        loading,
        isSaving,
        error
    }
}
