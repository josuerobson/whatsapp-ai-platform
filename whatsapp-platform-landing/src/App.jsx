import React, { useState } from 'react'
import { Button } from '@/components/ui/button.jsx'
import { Input } from '@/components/ui/input.jsx'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card.jsx'
import { Badge } from '@/components/ui/badge.jsx'
import { 
  MessageCircle, 
  Bot, 
  Users, 
  Zap, 
  Shield, 
  BarChart3, 
  CheckCircle, 
  Star,
  ArrowRight,
  Menu,
  X,
  Phone,
  Mail,
  MapPin
} from 'lucide-react'
import './App.css'

function App() {
  const [isMenuOpen, setIsMenuOpen] = useState(false)
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    company: '',
    phone: ''
  })

  const handleInputChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    })
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    // Aqui seria feita a integração com o backend PHP
    console.log('Dados do formulário:', formData)
    alert('Cadastro realizado com sucesso! Entraremos em contato em breve.')
  }

  const features = [
    {
      icon: <Bot className="h-8 w-8 text-green-600" />,
      title: "IA Avançada",
      description: "Respostas automáticas inteligentes com Google Gemini, personalizadas para seu negócio."
    },
    {
      icon: <Users className="h-8 w-8 text-green-600" />,
      title: "Multi-usuário",
      description: "Múltiplos agentes podem responder o mesmo WhatsApp simultaneamente."
    },
    {
      icon: <Zap className="h-8 w-8 text-green-600" />,
      title: "Automação N8N",
      description: "Fluxos de trabalho automatizados para otimizar seu atendimento."
    },
    {
      icon: <Shield className="h-8 w-8 text-green-600" />,
      title: "Seguro e Confiável",
      description: "Integração oficial com Evolution API, garantindo segurança total."
    },
    {
      icon: <BarChart3 className="h-8 w-8 text-green-600" />,
      title: "Analytics Completo",
      description: "Relatórios detalhados sobre performance e engajamento."
    },
    {
      icon: <MessageCircle className="h-8 w-8 text-green-600" />,
      title: "Interface Familiar",
      description: "Interface idêntica ao WhatsApp Web para facilidade de uso."
    }
  ]

  const plans = [
    {
      name: "Starter",
      price: "R$ 97",
      period: "/mês",
      description: "Ideal para pequenos negócios",
      features: [
        "1 número do WhatsApp",
        "Até 3 usuários simultâneos",
        "1.000 mensagens IA/mês",
        "Suporte por email",
        "Relatórios básicos"
      ],
      popular: false
    },
    {
      name: "Professional",
      price: "R$ 197",
      period: "/mês",
      description: "Para empresas em crescimento",
      features: [
        "3 números do WhatsApp",
        "Até 10 usuários simultâneos",
        "5.000 mensagens IA/mês",
        "Suporte prioritário",
        "Relatórios avançados",
        "Integração personalizada"
      ],
      popular: true
    },
    {
      name: "Enterprise",
      price: "R$ 397",
      period: "/mês",
      description: "Para grandes empresas",
      features: [
        "Números ilimitados",
        "Usuários ilimitados",
        "Mensagens IA ilimitadas",
        "Suporte 24/7",
        "Dashboard personalizado",
        "API dedicada",
        "Gerente de conta"
      ],
      popular: false
    }
  ]

  const testimonials = [
    {
      name: "Maria Silva",
      company: "E-commerce Fashion",
      text: "Aumentamos nossa conversão em 40% com as respostas automáticas inteligentes. A plataforma é incrível!",
      rating: 5
    },
    {
      name: "João Santos",
      company: "Consultoria Empresarial",
      text: "O atendimento 24/7 automatizado revolucionou nosso negócio. Clientes mais satisfeitos e equipe mais produtiva.",
      rating: 5
    },
    {
      name: "Ana Costa",
      company: "Clínica Médica",
      text: "A integração com nosso sistema foi perfeita. Agendamentos automáticos e lembretes reduziram faltas em 60%.",
      rating: 5
    }
  ]

  return (
    <div className="min-h-screen bg-white">
      {/* Header */}
      <header className="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-4">
            <div className="flex items-center">
              <MessageCircle className="h-8 w-8 text-green-600 mr-2" />
              <span className="text-xl font-bold text-gray-900">WhatsApp AI Platform</span>
            </div>
            
            {/* Desktop Navigation */}
            <nav className="hidden md:flex space-x-8">
              <a href="#features" className="text-gray-700 hover:text-green-600 transition-colors">Recursos</a>
              <a href="#pricing" className="text-gray-700 hover:text-green-600 transition-colors">Preços</a>
              <a href="#testimonials" className="text-gray-700 hover:text-green-600 transition-colors">Depoimentos</a>
              <a href="#contact" className="text-gray-700 hover:text-green-600 transition-colors">Contato</a>
            </nav>

            {/* Mobile menu button */}
            <button
              className="md:hidden"
              onClick={() => setIsMenuOpen(!isMenuOpen)}
            >
              {isMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
            </button>
          </div>

          {/* Mobile Navigation */}
          {isMenuOpen && (
            <nav className="md:hidden py-4 border-t border-gray-200">
              <div className="flex flex-col space-y-4">
                <a href="#features" className="text-gray-700 hover:text-green-600 transition-colors">Recursos</a>
                <a href="#pricing" className="text-gray-700 hover:text-green-600 transition-colors">Preços</a>
                <a href="#testimonials" className="text-gray-700 hover:text-green-600 transition-colors">Depoimentos</a>
                <a href="#contact" className="text-gray-700 hover:text-green-600 transition-colors">Contato</a>
              </div>
            </nav>
          )}
        </div>
      </header>

      {/* Hero Section */}
      <section className="bg-gradient-to-br from-green-50 to-green-100 py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
              <Badge className="mb-4 bg-green-100 text-green-800 hover:bg-green-200">
                🚀 Nova Era do Atendimento
              </Badge>
              <h1 className="text-4xl lg:text-6xl font-bold text-gray-900 mb-6">
                Transforme seu WhatsApp em uma
                <span className="text-green-600"> Máquina de Vendas</span>
              </h1>
              <p className="text-xl text-gray-600 mb-8">
                Automatize respostas com IA, gerencie múltiplos atendentes e aumente suas conversões 
                com a plataforma mais avançada de automação para WhatsApp.
              </p>
              <div className="flex flex-col sm:flex-row gap-4">
                <Button size="lg" className="bg-green-600 hover:bg-green-700 text-white px-8 py-3">
                  Começar Gratuitamente
                  <ArrowRight className="ml-2 h-5 w-5" />
                </Button>
                <Button variant="outline" size="lg" className="px-8 py-3">
                  Ver Demonstração
                </Button>
              </div>
            </div>
            
            <div className="relative">
              <div className="bg-white rounded-2xl shadow-2xl p-6 transform rotate-3 hover:rotate-0 transition-transform duration-300">
                <div className="bg-green-600 text-white p-4 rounded-t-lg">
                  <div className="flex items-center space-x-2">
                    <div className="w-3 h-3 bg-white rounded-full"></div>
                    <div className="w-3 h-3 bg-white rounded-full"></div>
                    <div className="w-3 h-3 bg-white rounded-full"></div>
                  </div>
                </div>
                <div className="p-4 space-y-4">
                  <div className="bg-gray-100 p-3 rounded-lg">
                    <p className="text-sm text-gray-600">Cliente: Olá, gostaria de saber sobre seus produtos</p>
                  </div>
                  <div className="bg-green-100 p-3 rounded-lg ml-8">
                    <p className="text-sm text-gray-800">🤖 IA: Olá! Ficamos felizes com seu interesse. Temos diversas opções incríveis para você. Que tipo de produto está procurando?</p>
                  </div>
                  <div className="bg-gray-100 p-3 rounded-lg">
                    <p className="text-sm text-gray-600">Cliente: Preciso de algo para minha empresa</p>
                  </div>
                  <div className="bg-green-100 p-3 rounded-lg ml-8">
                    <p className="text-sm text-gray-800">🤖 IA: Perfeito! Para empresas, recomendo nosso plano Professional. Posso agendar uma demonstração gratuita para você?</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="features" className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
              Recursos que Fazem a Diferença
            </h2>
            <p className="text-xl text-gray-600 max-w-3xl mx-auto">
              Nossa plataforma combina o poder da inteligência artificial com a praticidade 
              do WhatsApp para revolucionar seu atendimento ao cliente.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {features.map((feature, index) => (
              <Card key={index} className="border-0 shadow-lg hover:shadow-xl transition-shadow duration-300">
                <CardHeader>
                  <div className="mb-4">{feature.icon}</div>
                  <CardTitle className="text-xl">{feature.title}</CardTitle>
                </CardHeader>
                <CardContent>
                  <CardDescription className="text-gray-600">
                    {feature.description}
                  </CardDescription>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </section>

      {/* Pricing Section */}
      <section id="pricing" className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
              Planos para Todos os Tamanhos
            </h2>
            <p className="text-xl text-gray-600">
              Escolha o plano ideal para seu negócio e comece a transformar seu atendimento hoje mesmo.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {plans.map((plan, index) => (
              <Card key={index} className={`relative ${plan.popular ? 'border-green-600 border-2' : 'border-gray-200'}`}>
                {plan.popular && (
                  <Badge className="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-green-600 text-white">
                    Mais Popular
                  </Badge>
                )}
                <CardHeader className="text-center">
                  <CardTitle className="text-2xl">{plan.name}</CardTitle>
                  <div className="mt-4">
                    <span className="text-4xl font-bold text-gray-900">{plan.price}</span>
                    <span className="text-gray-600">{plan.period}</span>
                  </div>
                  <CardDescription>{plan.description}</CardDescription>
                </CardHeader>
                <CardContent>
                  <ul className="space-y-3 mb-8">
                    {plan.features.map((feature, featureIndex) => (
                      <li key={featureIndex} className="flex items-center">
                        <CheckCircle className="h-5 w-5 text-green-600 mr-3" />
                        <span className="text-gray-700">{feature}</span>
                      </li>
                    ))}
                  </ul>
                  <Button 
                    className={`w-full ${plan.popular ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-900 hover:bg-gray-800'}`}
                    size="lg"
                  >
                    Começar Agora
                  </Button>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </section>

      {/* Testimonials Section */}
      <section id="testimonials" className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
              O que Nossos Clientes Dizem
            </h2>
            <p className="text-xl text-gray-600">
              Empresas de todos os tamanhos já transformaram seus resultados com nossa plataforma.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {testimonials.map((testimonial, index) => (
              <Card key={index} className="border-0 shadow-lg">
                <CardContent className="p-6">
                  <div className="flex mb-4">
                    {[...Array(testimonial.rating)].map((_, i) => (
                      <Star key={i} className="h-5 w-5 text-yellow-400 fill-current" />
                    ))}
                  </div>
                  <p className="text-gray-700 mb-6 italic">"{testimonial.text}"</p>
                  <div>
                    <p className="font-semibold text-gray-900">{testimonial.name}</p>
                    <p className="text-gray-600 text-sm">{testimonial.company}</p>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </section>

      {/* Contact/Signup Section */}
      <section id="contact" className="py-20 bg-green-600">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl lg:text-4xl font-bold text-white mb-4">
              Pronto para Revolucionar seu Atendimento?
            </h2>
            <p className="text-xl text-green-100">
              Cadastre-se agora e receba 7 dias grátis para testar todos os recursos.
            </p>
          </div>

          <Card className="max-w-2xl mx-auto">
            <CardHeader>
              <CardTitle className="text-center text-2xl">Cadastro Gratuito</CardTitle>
              <CardDescription className="text-center">
                Preencha os dados abaixo e nossa equipe entrará em contato em até 24 horas.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubmit} className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Nome Completo *
                    </label>
                    <Input
                      type="text"
                      name="name"
                      value={formData.name}
                      onChange={handleInputChange}
                      required
                      placeholder="Seu nome completo"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Email *
                    </label>
                    <Input
                      type="email"
                      name="email"
                      value={formData.email}
                      onChange={handleInputChange}
                      required
                      placeholder="seu@email.com"
                    />
                  </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Empresa
                    </label>
                    <Input
                      type="text"
                      name="company"
                      value={formData.company}
                      onChange={handleInputChange}
                      placeholder="Nome da sua empresa"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Telefone *
                    </label>
                    <Input
                      type="tel"
                      name="phone"
                      value={formData.phone}
                      onChange={handleInputChange}
                      required
                      placeholder="(11) 99999-9999"
                    />
                  </div>
                </div>

                <Button type="submit" size="lg" className="w-full bg-green-600 hover:bg-green-700">
                  Começar Meu Teste Gratuito
                  <ArrowRight className="ml-2 h-5 w-5" />
                </Button>
              </form>
            </CardContent>
          </Card>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
              <div className="flex items-center mb-4">
                <MessageCircle className="h-8 w-8 text-green-600 mr-2" />
                <span className="text-xl font-bold">WhatsApp AI Platform</span>
              </div>
              <p className="text-gray-400">
                A plataforma mais avançada para automação de WhatsApp com inteligência artificial.
              </p>
            </div>
            
            <div>
              <h3 className="text-lg font-semibold mb-4">Produto</h3>
              <ul className="space-y-2 text-gray-400">
                <li><a href="#features" className="hover:text-white transition-colors">Recursos</a></li>
                <li><a href="#pricing" className="hover:text-white transition-colors">Preços</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Documentação</a></li>
                <li><a href="#" className="hover:text-white transition-colors">API</a></li>
              </ul>
            </div>
            
            <div>
              <h3 className="text-lg font-semibold mb-4">Empresa</h3>
              <ul className="space-y-2 text-gray-400">
                <li><a href="#" className="hover:text-white transition-colors">Sobre</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Blog</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Carreiras</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Imprensa</a></li>
              </ul>
            </div>
            
            <div>
              <h3 className="text-lg font-semibold mb-4">Contato</h3>
              <ul className="space-y-2 text-gray-400">
                <li className="flex items-center">
                  <Mail className="h-4 w-4 mr-2" />
                  contato@whatsappai.com
                </li>
                <li className="flex items-center">
                  <Phone className="h-4 w-4 mr-2" />
                  (11) 9999-9999
                </li>
                <li className="flex items-center">
                  <MapPin className="h-4 w-4 mr-2" />
                  São Paulo, SP
                </li>
              </ul>
            </div>
          </div>
          
          <div className="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; 2024 WhatsApp AI Platform. Todos os direitos reservados.</p>
          </div>
        </div>
      </footer>
    </div>
  )
}

export default App
