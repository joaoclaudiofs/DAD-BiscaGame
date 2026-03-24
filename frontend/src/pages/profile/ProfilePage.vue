<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800 py-8">
        <div class="mx-auto max-w-5xl px-6">
            <div class="mb-8 flex items-center gap-4">
                <button @click="$router.back()"
                    class="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors">
                    <ArrowLeft class="h-6 w-6 text-slate-700 dark:text-slate-300" />
                </button>
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-50">
                        Configurações de Perfil
                    </h1>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white dark:bg-slate-800 shadow">
                <div
                    class="border-b border-slate-200 dark:border-slate-700 bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-500 dark:to-indigo-500 px-6 py-8">
                    <div class="flex items-center space-x-6">
                        <Avatar class="size-24 border-4 border-white shadow-lg">
                            <AvatarImage :src="authStore.currentUser?.photo_url" :alt="authStore.currentUser?.name" />
                            <AvatarFallback class="text-2xl">
                                {{ getInitials(authStore.currentUser?.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-white">{{ authStore.currentUser?.name }}</h2>
                            <p class="text-blue-100">@{{ authStore.currentUser?.nickname }}</p>
                            <div class="mt-2 flex items-center space-x-4 text-sm text-blue-100">
                                <span>{{ authStore.currentUser?.email }}</span>
                                <span v-if="!authStore.isAnonymous" class="flex items-center">
                                    <svg class="mr-1 size-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ authStore.currentUser?.coins_balance }} moedas
                                </span>
                            </div>
                        </div>
                        <Button @click="isEditing = !isEditing" variant="secondary"
                            class="bg-white text-blue-600 hover:bg-blue-50">
                            <svg v-if="!isEditing" class="mr-2 size-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <svg v-else class="mr-2 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            {{ isEditing ? 'Cancelar' : 'Editar Perfil' }}
                        </Button>
                    </div>
                </div>

                <div class="p-6">
                    <form @submit.prevent="handleUpdateProfile" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <Label for="name">Nome Completo</Label>
                                <Input id="name" v-model="profileForm.name" :disabled="!isEditing" required
                                    class="mt-1" />
                            </div>
                            <div>
                                <Label for="nickname">Nickname</Label>
                                <Input id="nickname" v-model="profileForm.nickname" :disabled="!isEditing" required
                                    class="mt-1" />
                            </div>
                            <div>
                                <Label for="email">Endereço de Email</Label>
                                <Input id="email" v-model="profileForm.email" type="email" :disabled="!isEditing"
                                    required class="mt-1" />
                            </div>
                            <div>
                                <Label for="photo">Foto de Perfil</Label>
                                <Input id="photo" type="file" @change="handlePhotoChange" :disabled="!isEditing"
                                    accept="image/*" class="mt-1" />
                                <p class="mt-1 text-xs text-gray-500">JPG, PNG ou GIF (máx. 2MB)</p>
                            </div>
                        </div>
                        <div v-if="isEditing" class="flex justify-end space-x-3">
                            <Button type="button" variant="outline" @click="cancelEdit"> Cancelar </Button>
                            <Button type="submit"> Guardar Alterações </Button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="mt-6 overflow-hidden rounded-lg bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Alterar Password</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Atualiza a tua password para manteres a tua conta segura
                    </p>
                </div>
                <div class="p-6">
                    <form @submit.prevent="handleChangePassword" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <Label for="current_password">Password Atual</Label>
                                <Input id="current_password" v-model="passwordForm.current_password" type="password"
                                    required class="mt-1" />
                            </div>
                            <div>
                                <Label for="new_password">Nova Password</Label>
                                <Input id="new_password" v-model="passwordForm.new_password" type="password" required
                                    class="mt-1" />
                            </div>
                            <div>
                                <Label for="new_password_confirmation">Confirmar Nova Password</Label>
                                <Input id="new_password_confirmation" v-model="passwordForm.new_password_confirmation"
                                    type="password" required class="mt-1" />
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <Button type="submit"> Atualizar Password </Button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-6">
                        <div class="flex items-start">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900">Terminar Sessão</h3>
                                <p class="mt-1 text-sm text-gray-600">Termina a tua sessão atual</p>
                            </div>
                            <Button @click="handleSignOut" variant="outline">
                                <svg class="mr-2 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Terminar Sessão
                            </Button>
                        </div>
                    </div>
                </div>
                <div v-if="!authStore.isAdmin" class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-6">
                        <div class="flex items-start">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-red-600">Eliminar Conta</h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Elimina permanentemente a tua conta e todos os dados
                                </p>
                            </div>
                            <Button @click="showDeleteDialog = true" variant="destructive">
                                <svg class="mr-2 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Eliminar Conta
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Dialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="text-red-600">Eliminar Conta</DialogTitle>
                    <DialogDescription>
                        Esta ação não pode ser desfeita. Todos os teus dados, incluindo
                        {{ authStore.currentUser?.coins_balance }} moedas, serão permanentemente eliminados.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="handleDeleteAccount" class="space-y-4">
                    <div>
                        <Label for="confirmation">Escreve o teu nickname para confirmar:
                            <strong>{{ authStore.currentUser?.nickname }}</strong></Label>
                        <Input id="confirmation" v-model="deleteForm.confirmation" required class="mt-1"
                            placeholder="Escreve o teu nickname" />
                    </div>
                    <div>
                        <Label for="delete_password">Escreve a tua password</Label>
                        <Input id="delete_password" v-model="deleteForm.password" type="password" required
                            class="mt-1" />
                    </div>
                    <DialogFooter class="sm:justify-between">
                        <Button type="button" variant="outline" @click="showDeleteDialog = false">
                            Cancelar
                        </Button>
                        <Button type="submit" variant="destructive"> Eliminar Conta </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { toast } from 'vue-sonner'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog';
import { ArrowLeft } from 'lucide-vue-next'

const authStore = useAuthStore()
const router = useRouter()

const isEditing = ref(false)
const showDeleteDialog = ref(false)

const profileForm = reactive({
    name: '',
    nickname: '',
    email: '',
    photo: null,
})

const passwordForm = reactive({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
})

const deleteForm = reactive({
    confirmation: '',
    password: '',
})

const getInitials = (name) => {
    if (!name) return '?'
    const parts = name.split(' ')
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase()
    }
    return name.substring(0, 2).toUpperCase()
}

const loadProfile = () => {
    if (authStore.currentUser) {
        profileForm.name = authStore.currentUser.name || ''
        profileForm.nickname = authStore.currentUser.nickname || ''
        profileForm.email = authStore.currentUser.email || ''
    }
}

const handlePhotoChange = (event) => {
    const file = event.target.files[0]
    if (file) {
        //validar tamanho maximo 2MB
        if (file.size > 2 * 1024 * 1024) {
            toast.error('O tamanho do ficheiro deve ser inferior a 2MB')
            event.target.value = ''
            return
        }
        profileForm.photo = file
    }
}

const handleUpdateProfile = async () => {
    try {
        const updateData = {
            name: profileForm.name,
            nickname: profileForm.nickname,
            email: profileForm.email,
        }

        if (profileForm.photo) {
            updateData.photo_avatar_filename = profileForm.photo
        }

        await toast.promise(authStore.updateProfile(updateData), {
            loading: 'A atualizar perfil...',
            success: 'Perfil atualizado com sucesso!',
            error: (err) => `Erro: ${err?.response?.data?.message || 'Falha ao atualizar perfil'}`,
        })

        isEditing.value = false
        profileForm.photo = null
    } catch (error) {
        console.error('Erro ao atualizar perfil:', error)
    }
}

const cancelEdit = () => {
    isEditing.value = false
    loadProfile()
    profileForm.photo = null
}

const handleChangePassword = async () => {
    if (passwordForm.new_password !== passwordForm.new_password_confirmation) {
        toast.error('As novas passwords não são iguais')
        return
    }

    if (passwordForm.new_password.length < 3) {
        toast.error('A password deve ter pelo menos 3 caracteres')
        return
    }

    try {
        await toast.promise(
            authStore.updatePassword({
                current_password: passwordForm.current_password,
                password: passwordForm.new_password,
                password_confirmation: passwordForm.new_password_confirmation,
            }),
            {
                loading: 'A atualizar password...',
                success: 'Password atualizada com sucesso!',
                error: (err) => `Erro: ${err?.response?.data?.message || 'Falha ao atualizar password'}`,
            },
        )
        passwordForm.current_password = ''
        passwordForm.new_password = ''
        passwordForm.new_password_confirmation = ''
    } catch (error) {
        console.error('Erro ao atualizar password:', error)
    }
}

const handleSignOut = async () => {
    try {
        await toast.promise(authStore.logout(), {
            loading: 'A sair...',
            success: 'Saida efetuada com sucesso!',
            error: 'Falha ao sair da conta!',
        })
        router.push('/login')
    } catch (error) {
        console.error('Erro ao sair da conta:', error)
    }
}

const handleDeleteAccount = async () => {
    if (deleteForm.confirmation !== authStore.currentUser?.nickname) {
        toast.error('O nickname não corresponde!')
        return
    }

    try {
        await toast.promise(
            authStore.deleteAccount({
                nickname: deleteForm.confirmation,
                password: deleteForm.password,
            }),
            {
                loading: 'A eliminar conta...',
                success: 'Conta eliminada com sucesso!',
                error: (err) => `Erro: ${err?.response?.data?.message || 'Falha ao eliminar conta!'}`,
            },
        )

        showDeleteDialog.value = false
        router.push('/login')
    } catch (error) {
        console.error('Erro ao eliminar conta:', error)
    }
}

onMounted(() => {
    loadProfile()
})
</script>
