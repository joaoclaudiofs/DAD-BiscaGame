<template>
  <div
    class="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800 px-6 py-12"
  >
    <div class="w-full max-w-lg space-y-8">
      <div>
        <h2 class="text-center text-4xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
          Sign in to your account
        </h2>
        <p class="mt-3 text-center text-base text-slate-600 dark:text-slate-400">
          Enter your credentials to access your account
        </p>
      </div>

      <form
        class="mt-10 space-y-6 bg-white dark:bg-slate-800 p-8 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700"
        @submit.prevent="handleSubmit"
      >
        <div class="space-y-5">
          <div>
            <label
              for="email"
              class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
            >
              Email address
            </label>
            <Input
              id="email"
              v-model="formData.email"
              type="email"
              autocomplete="email"
              required
              placeholder="you@example.com"
              class="h-11"
            />
          </div>

          <div>
            <label
              for="password"
              class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
            >
              Password
            </label>
            <Input
              id="password"
              v-model="formData.password"
              type="password"
              autocomplete="current-password"
              required
              placeholder="••••••••"
              class="h-11"
            />
          </div>
        </div>

        <div>
          <Button type="submit" class="w-full h-12 text-base"> Sign in </Button>
        </div>

        <div class="text-center text-sm">
          <span class="text-slate-600 dark:text-slate-400">Don't have an account? </span>
          <a
            href="#"
            class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300"
          >
            Sign up
          </a>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'

import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'

const authStore = useAuthStore()
const router = useRouter()

const formData = ref({
  email: 'pa@mail.pt',
  password: '123',
})

const handleSubmit = async () => {
  toast.promise(authStore.login(formData.value), {
    loading: 'A fazer chamada à API',
    success: (data) => {
      return `Login feito com sucesso - ${data?.name}`
    },
    error: (data) => `[API] Erro ao fazer login - ${data?.response?.data?.message}`,
  })

  router.push('/dashboard')
}
</script>
