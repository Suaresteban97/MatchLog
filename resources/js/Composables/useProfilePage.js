import { ref } from 'vue';

/**
 * Maneja el estado de tabs y navegación interna de la página de Perfil:
 * - Tab activa: 'profile' | 'devices'
 * - Sub-vista de dispositivos: 'list' | 'create' | 'edit'
 */
export function useProfilePage(deviceFns) {
    const { loadCatalog, loadDevice, loadDevices, form } = deviceFns;

    const activeTab = ref('profile');
    const deviceView = ref('list');
    const editingDeviceId = ref(null);

    const goToCreate = async () => {
        await loadCatalog();
        form.device_id = '';
        form.custom_name = '';
        form.characteristics = [];
        editingDeviceId.value = null;
        deviceView.value = 'create';
        activeTab.value = 'devices';
    };

    const goToEdit = async (id) => {
        await loadCatalog();
        await loadDevice(id);
        editingDeviceId.value = id;
        deviceView.value = 'edit';
        activeTab.value = 'devices';
    };

    const goToList = async () => {
        deviceView.value = 'list';
        await loadDevices();
    };

    return {
        activeTab,
        deviceView,
        editingDeviceId,
        goToCreate,
        goToEdit,
        goToList,
    };
}
