import HomePage from '@/pages/home/HomePage.vue'
import AuthPage from '@/pages/auth/AuthPage.vue'
import LaravelPage from '@/pages/testing/LaravelPage.vue'
import MatchHistoryPage from '@/pages/history/MatchHistoryPage.vue'
import MatchGamesPage from '@/pages/history/MatchGamesPage.vue'
import LeaderboardPage from '@/pages/leaderboard/LeaderboardPage.vue'
import DashboardPage from '@/pages/dashboard/DashboardPage.vue'
import WalletPage from '@/pages/wallet/WalletPage.vue'
import PersonalScorePage from '@/pages/personal_score/PersonalScorePage.vue'
import StatisticsPage from '@/pages/statistics/StatisticsPage.vue'
import WebsocketsPage from '@/pages/testing/WebsocketsPage.vue'
import LobbyPage from '@/pages/match/LobbyPage.vue'
import MatchPage from '@/pages/match/MatchPage.vue'
//import HistoryPage from '@/pages/history/MatchGamesPage.vue'
import ProfilePage from '@/pages/profile/ProfilePage.vue'
import AdminUsersPage from '@/pages/admin/AdminUsersPage.vue'
import AdminMatchesPage from '@/pages/admin/AdminMatchesPage.vue'
import AdminGamesPage from '@/pages/admin/AdminGamesPage.vue'
import AdminPlayerHistory from '@/pages/admin/AdminPlayerHistory.vue'
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import GamePage from '@/pages/bot/GamePage.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: HomePage,
    },
    {
      path: '/dashboard',
      component: DashboardPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/login',
      component: AuthPage,
    },
    {
      path: '/history',
      component: MatchHistoryPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/history/:matchId/games',
      name: 'match-games',
      component: MatchGamesPage,
      meta: { requiresAuth: true, requiresNotAdmin: true },
    },
    {
      path: '/stats',
      component: PersonalScorePage,
      meta: { requiresAuth: true },
    },
    {
      path: '/statistics',
      component: StatisticsPage,
      meta: { requiresAuth: true },
    },
    {
      path: '/profile',
      component: ProfilePage,
      meta: { requiresAuth: true },
    },
    {
      path: '/leaderboard',
      component: LeaderboardPage,
    },
    {
      path: '/wallet',
      component: WalletPage,
    },
    {
      path: '/admin/users',
      component: AdminUsersPage,
      meta: { requiresAdmin: true },
    },
    {
      path: '/admin/matches',
      component: AdminMatchesPage,
      meta: { requiresAdmin: true },
    },
    {
      path: '/admin/matches/:matchId/games',
      name: 'admin-match-games',
      component: AdminGamesPage,
      meta: { requiresAdmin: true },
    },
    {
      path: '/admin/player-history',
      component: AdminPlayerHistory,
      meta: { requiresAdmin: true },
    },
    {
      path: "/game",
      name: "game9",
      component: GamePage,
    },
    {
      path: "/game/3",
      name: "game3",
      component: GamePage,
    },
    {
      path: '/match',
      meta: { requiresAuth: true },
      children: [
        {
          path: 'lobby',
          component: LobbyPage,
        },
        {
          path: ':id',
          component: MatchPage
        }
      ],
    },
    /*{
      path: '/testing',
      meta: { requiresAuth: true },
      children: [
        {
          path: 'laravel',
          component: LaravelPage,
        },
        {
          path: 'websockets',
          component: WebsocketsPage,
        },
      ],
    },*/
  ],
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  //verifica se a rota requer ser admin
  if (to.meta.requiresAdmin) {
    if (authStore.currentUser?.type !== 'A') {
      next('/dashboard')
      return
    }
  }

  //verifica se a rota não está disponivel para admin
  if (to.meta.requiresNotAdmin) {
    if (authStore.currentUser?.type === 'A') {
      next('/dashboard')
      return
    }
  }

//verifica se a rota precisa de autenticação
  if (to.meta.requiresAuth) {
    const isGuest = authStore.isAnonymous
    const isLogged = authStore.isLoggedIn
    //se não estiver logado e não for guest, vai pra login
    if (!isLogged && !isGuest) {
      next('/login')
      return
    }
  }

  next()
})

export default router
