<template>
    <div class="flex min-h-[calc(100vh-6rem)] items-center justify-center px-4 py-10">
        <Card class="w-full max-w-md">
            <CardHeader class="space-y-1">
                <CardTitle>Autenticação</CardTitle>
                <CardDescription>Faz login ou cria uma conta</CardDescription>
            </CardHeader>

            <CardContent>
                <Tabs v-model="activeTab" class="w-full">
                    <TabsList>
                        <TabsTrigger value="login">Login</TabsTrigger>
                        <TabsTrigger value="register">Registar</TabsTrigger>
                    </TabsList>

                    <TabsContent value="login">
                        <form class="space-y-4" @submit.prevent="handleLogin">
                            <div class="space-y-2">
                                <label class="text-sm font-medium" for="login-email">
                                    Endereço de Email
                                </label>
                                <div class="relative">
                                    <Mail
                                        class="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                                    <Input id="login-email" v-model="loginForm.email" type="email" autocomplete="email"
                                        placeholder="you@example.com" required class="pl-9" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium" for="login-password">
                                    Password
                                </label>
                                <div class="relative">
                                    <Lock
                                        class="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                                    <Input id="login-password" v-model="loginForm.password" type="password"
                                        autocomplete="current-password" placeholder="••••••••" required class="pl-9" />
                                </div>
                            </div>

                            <Button type="submit" class="w-full" :disabled="isLoginSubmitting">
                                {{ isLoginSubmitting ? 'A iniciar sessão...' : 'Iniciar Sessão' }}
                            </Button>

                        </form>
                    </TabsContent>

                    <TabsContent value="register">
                        <div class="space-y-4">
                            <CardDescription>
                                Cria uma conta hoje e recebe um
                                <span class="font-semibold">bónus de boas-vindas de 10 moedas</span>!
                            </CardDescription>

                            <form class="space-y-4" @submit.prevent="handleRegister">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium" for="register-fullname">
                                        Nome Completo
                                    </label>
                                    <div class="relative">
                                        <User
                                            class="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                                        <Input id="register-fullname" v-model="registerForm.name" autocomplete="name"
                                            placeholder="O teu nome completo" required class="pl-9" />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium" for="register-nickname">
                                        Nickname (ID Único)
                                    </label>
                                    <div class="relative">
                                        <AtSign
                                            class="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                                        <Input id="register-nickname" v-model="registerForm.nickname"
                                            autocomplete="username" placeholder="O teu nickname" required
                                            class="pl-9" />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium" for="register-email">
                                        Endereço de Email
                                    </label>
                                    <div class="relative">
                                        <Mail
                                            class="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                                        <Input id="register-email" v-model="registerForm.email" type="email"
                                            autocomplete="email" placeholder="tu@exemplo.com" required class="pl-9" />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium" for="register-password">
                                        Password
                                    </label>
                                    <div class="relative">
                                        <Lock
                                            class="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                                        <Input id="register-password" v-model="registerForm.password" type="password"
                                            autocomplete="new-password" placeholder="••••••••" minlength="3" required
                                            class="pl-9" />
                                    </div>
                                    <p class="text-muted-foreground text-sm">
                                        Mínimo de 3 caracteres.
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium" for="register-password-confirm">
                                        Confirmar Password
                                    </label>
                                    <div class="relative">
                                        <Lock
                                            class="text-muted-foreground absolute left-3 top-1/2 size-4 -translate-y-1/2" />
                                        <Input id="register-password-confirm"
                                            v-model="registerForm.password_confirmation" type="password"
                                            autocomplete="new-password" placeholder="••••••••" minlength="8" required
                                            :class="[!passwordsMatch ? 'border-destructive focus-visible:ring-destructive/20' : '', 'pl-9']" />
                                    </div>
                                    <p v-if="!passwordsMatch" class="text-destructive text-sm font-medium">
                                        As passwords não são iguais.
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">
                                        Escolher Avatar (Opcional)
                                    </label>
                                    <div class="flex flex-wrap gap-2">
                                        <Button type="button" @click="openAvatarDialog" variant="outline"
                                            class="w-full sm:w-auto">
                                            <ImageUp class="mr-2 size-4" />
                                            Escolher Foto
                                        </Button>
                                        <Button v-if="registerForm.photo" type="button"
                                            @click="resetAvatar(); registerForm.photo = null" variant="ghost"
                                            class="w-full sm:w-auto">
                                            Limpar
                                        </Button>
                                    </div>
                                    <p v-if="registerForm.photo" class="text-sm text-muted-foreground">
                                        Selecionado: {{ registerForm.photo.name }}
                                    </p>
                                </div>

                                <Button type="submit" class="w-full"
                                    :disabled="!canSubmitRegister || isRegisterSubmitting">
                                    {{ isRegisterSubmitting ? 'A criar conta...' : 'Criar Conta e Receber Bónus'
                                    }}
                                </Button>
                            </form>
                        </div>
                    </TabsContent>
                </Tabs>
            </CardContent>
        </Card>
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue";
import { Mail, Lock, User, AtSign, ImageUp } from "lucide-vue-next";

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";
import { toast } from "vue-sonner";
import { useFileDialog } from '@vueuse/core';

const authStore = useAuthStore();
const router = useRouter();

const activeTab = ref("login");
const isLoginSubmitting = ref(false);
const isRegisterSubmitting = ref(false);

const loginForm = ref({
    email: 'pa@mail.pt',
    password: '123'
})

const registerForm = reactive({
    name: "",
    nickname: "",
    email: "",
    password: "",
    password_confirmation: "",
    photo: /** @type {File|null} */ (null),
});

const passwordsMatch = computed(() => {
    if (!registerForm.password_confirmation) return true;
    return registerForm.password === registerForm.password_confirmation;
});

const canSubmitRegister = computed(() => {
    return registerForm.name &&
        registerForm.nickname &&
        registerForm.email &&
        registerForm.password.length >= 8 &&
        passwordsMatch.value;
});

const handleLogin = async () => {
    if (isLoginSubmitting.value) return
    isLoginSubmitting.value = true

    try {
        const user = await authStore.login(loginForm.value)
        toast.success(`Login realizado - ${user?.name || 'Jogador'}`)
        console.log('Login realizado:', user)
        router.push('/dashboard')
    } catch (error) {
        console.error('Login falhou:', error)
        toast.error(
            `[API] Erro ao fazer login - ${error?.response?.data?.message || 'Credenciais inválidas'}`
        )
    } finally {
        isLoginSubmitting.value = false
    }
};

const handleRegister = async () => {
    if (isRegisterSubmitting.value) return
    isRegisterSubmitting.value = true

    try {
        console.log("A registar utilizador:", registerForm);
        const user = await authStore.register(registerForm)

        toast.success(`Registo realizado - Bem-vindo ${user?.name || 'Jogador'}!`)

        console.log('Registo realizado:', user)

        router.push('/dashboard')
    } catch (error) {

        console.error('Registo falhou:', error)
        toast.error(
            `[API] Erro ao registar - ${error?.response?.data?.message || 'Registo falhou'}`
        )
    } finally {
        isRegisterSubmitting.value = false
    }
};

const { files: avatarFiles, open: openAvatarDialog, reset: resetAvatar } = useFileDialog({
    accept: 'image/*',
    multiple: false
});

watch(avatarFiles, (newFiles) => {
    if (newFiles && newFiles.length > 0) {
        registerForm.photo = newFiles[0];
    }
});
</script>
