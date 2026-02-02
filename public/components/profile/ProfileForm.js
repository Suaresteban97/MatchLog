
import General from '../General.js';

const { createApp } = Vue;

const ProfileForm = createApp({
    data() {
        return {
            profile: {
                first_name: '',
                last_name: '',
                nickname: '',
                age: '',
                genre: '',
                bio: '',
                photo: '',
                share_email: false,
                available_for_online: true
            },
            loading: true,
            saving: false,
            message: '',
            error: null
        }
    },
    async mounted() {
        await this.loadProfile();
    },
    methods: {
        async loadProfile() {
            this.loading = true;
            try {
                const res = await General.get('/profile');
                if (res.profile) {
                    this.profile = { ...this.profile, ...res.profile };
                    // Ensure booleans are correct (API might return 0/1)
                    this.profile.share_email = !!this.profile.share_email;
                    this.profile.available_for_online = !!this.profile.available_for_online;
                }
            } catch (err) {
                console.error(err);
                this.error = 'No se pudo cargar el perfil.';
            } finally {
                this.loading = false;
            }
        },
        async updateProfile() {
            this.saving = true;
            this.message = '';
            this.error = '';

            try {
                const res = await General.put('/profile', this.profile);
                this.message = res.message || 'Perfil actualizado correctamente';
                // Update local profile with response to sync any server-side changes
                if (res.profile) {
                    this.profile = { ...this.profile, ...res.profile };
                    this.profile.share_email = !!this.profile.share_email;
                    this.profile.available_for_online = !!this.profile.available_for_online;
                }

                // Scroll to top to see message
                window.scrollTo({ top: 0, behavior: 'smooth' });

            } catch (err) {
                console.error(err);
                this.error = err.message || 'Error al guardar el perfil.';
            } finally {
                this.saving = false;
            }
        }
    }
});

ProfileForm.mount("#profile-form");
