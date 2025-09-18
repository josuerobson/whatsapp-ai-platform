import React, { useState, useEffect } from 'react'
import { Button } from '@/components/ui/button.jsx'
import { Input } from '@/components/ui/input.jsx'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card.jsx'
import { Badge } from '@/components/ui/badge.jsx'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs.jsx'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table.jsx'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog.jsx'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select.jsx'
import { Textarea } from '@/components/ui/textarea.jsx'
import { Switch } from '@/components/ui/switch.jsx'
import { 
  Users, 
  DollarSign, 
  Activity, 
  TrendingUp,
  UserPlus,
  Edit,
  Trash2,
  Eye,
  CheckCircle,
  XCircle,
  Calendar,
  MessageCircle,
  Settings,
  BarChart3,
  Search,
  Filter,
  Download,
  Bell,
  LogOut,
  Shield
} from 'lucide-react'
import './App.css'

function App() {
  const [activeTab, setActiveTab] = useState('dashboard')
  const [searchTerm, setSearchTerm] = useState('')
  const [selectedUser, setSelectedUser] = useState(null)
  const [isAddUserOpen, setIsAddUserOpen] = useState(false)
  const [isEditUserOpen, setIsEditUserOpen] = useState(false)

  // Mock data - em produção viria do backend PHP
  const [dashboardStats, setDashboardStats] = useState({
    totalUsers: 156,
    activeUsers: 142,
    totalRevenue: 45680,
    monthlyGrowth: 12.5
  })

  const [users, setUsers] = useState([
    {
      id: 1,
      name: 'João Silva',
      email: 'joao@empresa.com',
      company: 'Tech Solutions',
      phone: '(11) 99999-1111',
      plan: 'Professional',
      status: 'active',
      monthlyFee: 197,
      lastPayment: '2024-09-15',
      createdAt: '2024-08-01',
      whatsappInstances: 2,
      messagesThisMonth: 3450
    },
    {
      id: 2,
      name: 'Maria Santos',
      email: 'maria@loja.com',
      company: 'Fashion Store',
      phone: '(11) 99999-2222',
      plan: 'Starter',
      status: 'pending',
      monthlyFee: 97,
      lastPayment: null,
      createdAt: '2024-09-10',
      whatsappInstances: 0,
      messagesThisMonth: 0
    },
    {
      id: 3,
      name: 'Carlos Oliveira',
      email: 'carlos@consultoria.com',
      company: 'Business Consulting',
      phone: '(11) 99999-3333',
      plan: 'Enterprise',
      status: 'active',
      monthlyFee: 397,
      lastPayment: '2024-09-12',
      createdAt: '2024-07-15',
      whatsappInstances: 5,
      messagesThisMonth: 8920
    },
    {
      id: 4,
      name: 'Ana Costa',
      email: 'ana@clinica.com',
      company: 'Clínica Médica',
      phone: '(11) 99999-4444',
      plan: 'Professional',
      status: 'inactive',
      monthlyFee: 197,
      lastPayment: '2024-08-15',
      createdAt: '2024-06-20',
      whatsappInstances: 1,
      messagesThisMonth: 0
    }
  ])

  const [newUser, setNewUser] = useState({
    name: '',
    email: '',
    company: '',
    phone: '',
    plan: 'Starter',
    monthlyFee: 97
  })

  const planPrices = {
    'Starter': 97,
    'Professional': 197,
    'Enterprise': 397
  }

  const handleAddUser = () => {
    const user = {
      ...newUser,
      id: users.length + 1,
      status: 'pending',
      lastPayment: null,
      createdAt: new Date().toISOString().split('T')[0],
      whatsappInstances: 0,
      messagesThisMonth: 0
    }
    setUsers([...users, user])
    setNewUser({
      name: '',
      email: '',
      company: '',
      phone: '',
      plan: 'Starter',
      monthlyFee: 97
    })
    setIsAddUserOpen(false)
  }

  const handleEditUser = (user) => {
    setSelectedUser(user)
    setIsEditUserOpen(true)
  }

  const handleUpdateUser = () => {
    setUsers(users.map(u => u.id === selectedUser.id ? selectedUser : u))
    setIsEditUserOpen(false)
    setSelectedUser(null)
  }

  const handleDeleteUser = (userId) => {
    setUsers(users.filter(u => u.id !== userId))
  }

  const handleActivateUser = (userId) => {
    setUsers(users.map(u => 
      u.id === userId ? { ...u, status: 'active' } : u
    ))
  }

  const handleDeactivateUser = (userId) => {
    setUsers(users.map(u => 
      u.id === userId ? { ...u, status: 'inactive' } : u
    ))
  }

  const filteredUsers = users.filter(user =>
    user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    user.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
    user.company.toLowerCase().includes(searchTerm.toLowerCase())
  )

  const getStatusBadge = (status) => {
    const statusConfig = {
      active: { label: 'Ativo', className: 'bg-green-100 text-green-800' },
      pending: { label: 'Pendente', className: 'bg-yellow-100 text-yellow-800' },
      inactive: { label: 'Inativo', className: 'bg-red-100 text-red-800' }
    }
    const config = statusConfig[status] || statusConfig.pending
    return <Badge className={config.className}>{config.label}</Badge>
  }

  const getPlanBadge = (plan) => {
    const planConfig = {
      'Starter': { className: 'bg-blue-100 text-blue-800' },
      'Professional': { className: 'bg-purple-100 text-purple-800' },
      'Enterprise': { className: 'bg-orange-100 text-orange-800' }
    }
    const config = planConfig[plan] || planConfig.Starter
    return <Badge className={config.className}>{plan}</Badge>
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div className="px-6 py-4">
          <div className="flex justify-between items-center">
            <div className="flex items-center space-x-4">
              <div className="flex items-center">
                <Shield className="h-8 w-8 text-blue-600 mr-2" />
                <div>
                  <h1 className="text-xl font-bold text-gray-900">Painel Master</h1>
                  <p className="text-sm text-gray-500">WhatsApp AI Platform</p>
                </div>
              </div>
            </div>
            
            <div className="flex items-center space-x-4">
              <Button variant="ghost" size="sm">
                <Bell className="h-4 w-4" />
              </Button>
              <Button variant="ghost" size="sm">
                <Settings className="h-4 w-4" />
              </Button>
              <Button variant="outline" size="sm">
                <LogOut className="h-4 w-4 mr-2" />
                Sair
              </Button>
            </div>
          </div>
        </div>
      </header>

      <div className="flex">
        {/* Sidebar */}
        <aside className="w-64 bg-white border-r border-gray-200 min-h-screen">
          <nav className="p-4">
            <div className="space-y-2">
              <Button
                variant={activeTab === 'dashboard' ? 'default' : 'ghost'}
                className="w-full justify-start"
                onClick={() => setActiveTab('dashboard')}
              >
                <BarChart3 className="h-4 w-4 mr-2" />
                Dashboard
              </Button>
              <Button
                variant={activeTab === 'users' ? 'default' : 'ghost'}
                className="w-full justify-start"
                onClick={() => setActiveTab('users')}
              >
                <Users className="h-4 w-4 mr-2" />
                Usuários
              </Button>
              <Button
                variant={activeTab === 'billing' ? 'default' : 'ghost'}
                className="w-full justify-start"
                onClick={() => setActiveTab('billing')}
              >
                <DollarSign className="h-4 w-4 mr-2" />
                Faturamento
              </Button>
              <Button
                variant={activeTab === 'analytics' ? 'default' : 'ghost'}
                className="w-full justify-start"
                onClick={() => setActiveTab('analytics')}
              >
                <Activity className="h-4 w-4 mr-2" />
                Analytics
              </Button>
            </div>
          </nav>
        </aside>

        {/* Main Content */}
        <main className="flex-1 p-6">
          {activeTab === 'dashboard' && (
            <div className="space-y-6">
              <div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">Dashboard</h2>
                <p className="text-gray-600">Visão geral da plataforma</p>
              </div>

              {/* Stats Cards */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card>
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Total de Usuários</CardTitle>
                    <Users className="h-4 w-4 text-muted-foreground" />
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">{dashboardStats.totalUsers}</div>
                    <p className="text-xs text-muted-foreground">
                      +12% em relação ao mês passado
                    </p>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Usuários Ativos</CardTitle>
                    <Activity className="h-4 w-4 text-muted-foreground" />
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">{dashboardStats.activeUsers}</div>
                    <p className="text-xs text-muted-foreground">
                      {((dashboardStats.activeUsers / dashboardStats.totalUsers) * 100).toFixed(1)}% do total
                    </p>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Receita Mensal</CardTitle>
                    <DollarSign className="h-4 w-4 text-muted-foreground" />
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">R$ {dashboardStats.totalRevenue.toLocaleString()}</div>
                    <p className="text-xs text-muted-foreground">
                      +{dashboardStats.monthlyGrowth}% este mês
                    </p>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Crescimento</CardTitle>
                    <TrendingUp className="h-4 w-4 text-muted-foreground" />
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">+{dashboardStats.monthlyGrowth}%</div>
                    <p className="text-xs text-muted-foreground">
                      Crescimento mensal
                    </p>
                  </CardContent>
                </Card>
              </div>

              {/* Recent Activity */}
              <Card>
                <CardHeader>
                  <CardTitle>Atividade Recente</CardTitle>
                  <CardDescription>Últimas ações na plataforma</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    <div className="flex items-center space-x-4">
                      <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                      <div className="flex-1">
                        <p className="text-sm font-medium">Novo usuário cadastrado</p>
                        <p className="text-xs text-gray-500">Maria Santos - Fashion Store</p>
                      </div>
                      <p className="text-xs text-gray-500">2 min atrás</p>
                    </div>
                    <div className="flex items-center space-x-4">
                      <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                      <div className="flex-1">
                        <p className="text-sm font-medium">Pagamento processado</p>
                        <p className="text-xs text-gray-500">João Silva - R$ 197,00</p>
                      </div>
                      <p className="text-xs text-gray-500">1 hora atrás</p>
                    </div>
                    <div className="flex items-center space-x-4">
                      <div className="w-2 h-2 bg-yellow-500 rounded-full"></div>
                      <div className="flex-1">
                        <p className="text-sm font-medium">Usuário ativado</p>
                        <p className="text-xs text-gray-500">Carlos Oliveira - Enterprise</p>
                      </div>
                      <p className="text-xs text-gray-500">3 horas atrás</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          )}

          {activeTab === 'users' && (
            <div className="space-y-6">
              <div className="flex justify-between items-center">
                <div>
                  <h2 className="text-2xl font-bold text-gray-900 mb-2">Gerenciar Usuários</h2>
                  <p className="text-gray-600">Gerencie todos os usuários da plataforma</p>
                </div>
                <Dialog open={isAddUserOpen} onOpenChange={setIsAddUserOpen}>
                  <DialogTrigger asChild>
                    <Button>
                      <UserPlus className="h-4 w-4 mr-2" />
                      Adicionar Usuário
                    </Button>
                  </DialogTrigger>
                  <DialogContent className="max-w-md">
                    <DialogHeader>
                      <DialogTitle>Adicionar Novo Usuário</DialogTitle>
                      <DialogDescription>
                        Preencha os dados do novo usuário
                      </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                      <div>
                        <label className="text-sm font-medium">Nome Completo</label>
                        <Input
                          value={newUser.name}
                          onChange={(e) => setNewUser({...newUser, name: e.target.value})}
                          placeholder="Nome do usuário"
                        />
                      </div>
                      <div>
                        <label className="text-sm font-medium">Email</label>
                        <Input
                          type="email"
                          value={newUser.email}
                          onChange={(e) => setNewUser({...newUser, email: e.target.value})}
                          placeholder="email@exemplo.com"
                        />
                      </div>
                      <div>
                        <label className="text-sm font-medium">Empresa</label>
                        <Input
                          value={newUser.company}
                          onChange={(e) => setNewUser({...newUser, company: e.target.value})}
                          placeholder="Nome da empresa"
                        />
                      </div>
                      <div>
                        <label className="text-sm font-medium">Telefone</label>
                        <Input
                          value={newUser.phone}
                          onChange={(e) => setNewUser({...newUser, phone: e.target.value})}
                          placeholder="(11) 99999-9999"
                        />
                      </div>
                      <div>
                        <label className="text-sm font-medium">Plano</label>
                        <Select 
                          value={newUser.plan} 
                          onValueChange={(value) => setNewUser({
                            ...newUser, 
                            plan: value, 
                            monthlyFee: planPrices[value]
                          })}
                        >
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="Starter">Starter - R$ 97/mês</SelectItem>
                            <SelectItem value="Professional">Professional - R$ 197/mês</SelectItem>
                            <SelectItem value="Enterprise">Enterprise - R$ 397/mês</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                      <Button onClick={handleAddUser} className="w-full">
                        Adicionar Usuário
                      </Button>
                    </div>
                  </DialogContent>
                </Dialog>
              </div>

              {/* Search and Filters */}
              <div className="flex space-x-4">
                <div className="flex-1">
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                    <Input
                      placeholder="Buscar usuários..."
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      className="pl-10"
                    />
                  </div>
                </div>
                <Button variant="outline">
                  <Filter className="h-4 w-4 mr-2" />
                  Filtros
                </Button>
                <Button variant="outline">
                  <Download className="h-4 w-4 mr-2" />
                  Exportar
                </Button>
              </div>

              {/* Users Table */}
              <Card>
                <CardContent className="p-0">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Usuário</TableHead>
                        <TableHead>Empresa</TableHead>
                        <TableHead>Plano</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Mensalidade</TableHead>
                        <TableHead>Último Pagamento</TableHead>
                        <TableHead>Ações</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {filteredUsers.map((user) => (
                        <TableRow key={user.id}>
                          <TableCell>
                            <div>
                              <div className="font-medium">{user.name}</div>
                              <div className="text-sm text-gray-500">{user.email}</div>
                            </div>
                          </TableCell>
                          <TableCell>{user.company}</TableCell>
                          <TableCell>{getPlanBadge(user.plan)}</TableCell>
                          <TableCell>{getStatusBadge(user.status)}</TableCell>
                          <TableCell>R$ {user.monthlyFee}</TableCell>
                          <TableCell>
                            {user.lastPayment ? 
                              new Date(user.lastPayment).toLocaleDateString('pt-BR') : 
                              'Pendente'
                            }
                          </TableCell>
                          <TableCell>
                            <div className="flex space-x-2">
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => handleEditUser(user)}
                              >
                                <Edit className="h-4 w-4" />
                              </Button>
                              {user.status === 'pending' && (
                                <Button
                                  variant="ghost"
                                  size="sm"
                                  onClick={() => handleActivateUser(user.id)}
                                >
                                  <CheckCircle className="h-4 w-4 text-green-600" />
                                </Button>
                              )}
                              {user.status === 'active' && (
                                <Button
                                  variant="ghost"
                                  size="sm"
                                  onClick={() => handleDeactivateUser(user.id)}
                                >
                                  <XCircle className="h-4 w-4 text-red-600" />
                                </Button>
                              )}
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => handleDeleteUser(user.id)}
                              >
                                <Trash2 className="h-4 w-4 text-red-600" />
                              </Button>
                            </div>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </CardContent>
              </Card>
            </div>
          )}

          {activeTab === 'billing' && (
            <div className="space-y-6">
              <div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">Faturamento</h2>
                <p className="text-gray-600">Gerencie pagamentos e mensalidades</p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card>
                  <CardHeader>
                    <CardTitle className="text-sm font-medium">Receita Este Mês</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">R$ 15.840</div>
                    <p className="text-xs text-green-600">+8.2% vs mês anterior</p>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <CardTitle className="text-sm font-medium">Pagamentos Pendentes</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">R$ 2.940</div>
                    <p className="text-xs text-yellow-600">15 faturas em aberto</p>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <CardTitle className="text-sm font-medium">Taxa de Conversão</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">94.2%</div>
                    <p className="text-xs text-green-600">+2.1% vs mês anterior</p>
                  </CardContent>
                </Card>
              </div>

              <Card>
                <CardHeader>
                  <CardTitle>Histórico de Pagamentos</CardTitle>
                  <CardDescription>Últimos pagamentos processados</CardDescription>
                </CardHeader>
                <CardContent>
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Cliente</TableHead>
                        <TableHead>Plano</TableHead>
                        <TableHead>Valor</TableHead>
                        <TableHead>Data</TableHead>
                        <TableHead>Status</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      <TableRow>
                        <TableCell>João Silva</TableCell>
                        <TableCell>Professional</TableCell>
                        <TableCell>R$ 197,00</TableCell>
                        <TableCell>15/09/2024</TableCell>
                        <TableCell>
                          <Badge className="bg-green-100 text-green-800">Pago</Badge>
                        </TableCell>
                      </TableRow>
                      <TableRow>
                        <TableCell>Carlos Oliveira</TableCell>
                        <TableCell>Enterprise</TableCell>
                        <TableCell>R$ 397,00</TableCell>
                        <TableCell>12/09/2024</TableCell>
                        <TableCell>
                          <Badge className="bg-green-100 text-green-800">Pago</Badge>
                        </TableCell>
                      </TableRow>
                      <TableRow>
                        <TableCell>Ana Costa</TableCell>
                        <TableCell>Professional</TableCell>
                        <TableCell>R$ 197,00</TableCell>
                        <TableCell>15/08/2024</TableCell>
                        <TableCell>
                          <Badge className="bg-red-100 text-red-800">Vencido</Badge>
                        </TableCell>
                      </TableRow>
                    </TableBody>
                  </Table>
                </CardContent>
              </Card>
            </div>
          )}

          {activeTab === 'analytics' && (
            <div className="space-y-6">
              <div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">Analytics</h2>
                <p className="text-gray-600">Métricas e relatórios da plataforma</p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card>
                  <CardHeader>
                    <CardTitle className="text-sm font-medium">Mensagens Enviadas</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">127.5K</div>
                    <p className="text-xs text-green-600">+15.3% este mês</p>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <CardTitle className="text-sm font-medium">Instâncias Ativas</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">89</div>
                    <p className="text-xs text-blue-600">8 novas esta semana</p>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <CardTitle className="text-sm font-medium">Taxa de Resposta IA</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">96.8%</div>
                    <p className="text-xs text-green-600">+1.2% vs semana anterior</p>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <CardTitle className="text-sm font-medium">Uptime Médio</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">99.9%</div>
                    <p className="text-xs text-green-600">Excelente performance</p>
                  </CardContent>
                </Card>
              </div>

              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                  <CardHeader>
                    <CardTitle>Distribuição por Planos</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-4">
                      <div className="flex justify-between items-center">
                        <span>Starter</span>
                        <span className="font-medium">45%</span>
                      </div>
                      <div className="w-full bg-gray-200 rounded-full h-2">
                        <div className="bg-blue-600 h-2 rounded-full" style={{width: '45%'}}></div>
                      </div>
                      
                      <div className="flex justify-between items-center">
                        <span>Professional</span>
                        <span className="font-medium">35%</span>
                      </div>
                      <div className="w-full bg-gray-200 rounded-full h-2">
                        <div className="bg-purple-600 h-2 rounded-full" style={{width: '35%'}}></div>
                      </div>
                      
                      <div className="flex justify-between items-center">
                        <span>Enterprise</span>
                        <span className="font-medium">20%</span>
                      </div>
                      <div className="w-full bg-gray-200 rounded-full h-2">
                        <div className="bg-orange-600 h-2 rounded-full" style={{width: '20%'}}></div>
                      </div>
                    </div>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <CardTitle>Crescimento Mensal</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-4">
                      <div className="text-center">
                        <div className="text-3xl font-bold text-green-600">+12.5%</div>
                        <p className="text-sm text-gray-500">Crescimento este mês</p>
                      </div>
                      <div className="space-y-2">
                        <div className="flex justify-between text-sm">
                          <span>Novos usuários</span>
                          <span className="font-medium">+18</span>
                        </div>
                        <div className="flex justify-between text-sm">
                          <span>Receita</span>
                          <span className="font-medium">+R$ 1.840</span>
                        </div>
                        <div className="flex justify-between text-sm">
                          <span>Mensagens</span>
                          <span className="font-medium">+15.3K</span>
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </div>
            </div>
          )}
        </main>
      </div>

      {/* Edit User Dialog */}
      <Dialog open={isEditUserOpen} onOpenChange={setIsEditUserOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>Editar Usuário</DialogTitle>
            <DialogDescription>
              Atualize as informações do usuário
            </DialogDescription>
          </DialogHeader>
          {selectedUser && (
            <div className="space-y-4">
              <div>
                <label className="text-sm font-medium">Nome Completo</label>
                <Input
                  value={selectedUser.name}
                  onChange={(e) => setSelectedUser({...selectedUser, name: e.target.value})}
                />
              </div>
              <div>
                <label className="text-sm font-medium">Email</label>
                <Input
                  type="email"
                  value={selectedUser.email}
                  onChange={(e) => setSelectedUser({...selectedUser, email: e.target.value})}
                />
              </div>
              <div>
                <label className="text-sm font-medium">Empresa</label>
                <Input
                  value={selectedUser.company}
                  onChange={(e) => setSelectedUser({...selectedUser, company: e.target.value})}
                />
              </div>
              <div>
                <label className="text-sm font-medium">Plano</label>
                <Select 
                  value={selectedUser.plan} 
                  onValueChange={(value) => setSelectedUser({
                    ...selectedUser, 
                    plan: value, 
                    monthlyFee: planPrices[value]
                  })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Starter">Starter - R$ 97/mês</SelectItem>
                    <SelectItem value="Professional">Professional - R$ 197/mês</SelectItem>
                    <SelectItem value="Enterprise">Enterprise - R$ 397/mês</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <label className="text-sm font-medium">Status</label>
                <Select 
                  value={selectedUser.status} 
                  onValueChange={(value) => setSelectedUser({...selectedUser, status: value})}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="active">Ativo</SelectItem>
                    <SelectItem value="pending">Pendente</SelectItem>
                    <SelectItem value="inactive">Inativo</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <Button onClick={handleUpdateUser} className="w-full">
                Salvar Alterações
              </Button>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  )
}

export default App
