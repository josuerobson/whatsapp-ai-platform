import React, { useState, useEffect } from 'react'
import { Button } from '@/components/ui/button.jsx'
import { Input } from '@/components/ui/input.jsx'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card.jsx'
import { Badge } from '@/components/ui/badge.jsx'
import { Switch } from '@/components/ui/switch.jsx'
import { Textarea } from '@/components/ui/textarea.jsx'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog.jsx'
import { Tabs, TabsList, TabsContent, TabsTrigger } from '@/components/ui/tabs.jsx'
import { 
  MessageCircle, 
  Bot, 
  Users, 
  Settings, 
  LogOut, 
  PlusCircle, 
  Search, 
  Paperclip, 
  Send, 
  Check, 
  CheckCheck, 
  Clock, 
  Power, 
  PowerOff, 
  QrCode, 
  RefreshCw 
} from 'lucide-react'
import './App.css'

// Mock de dados que viriam do backend PHP
const initialConversations = [
  {
    id: 1,
    name: 'Cliente Feliz',
    lastMessage: 'Ótimo, muito obrigado! Resolveu meu problema.',
    timestamp: '10:45',
    unread: 0,
    avatar: 'https://i.pravatar.cc/150?img=1',
    autoReply: true,
    messages: [
      { id: 1, text: 'Olá, preciso de ajuda com meu pedido.', sender: 'user', time: '10:40' },
      { id: 2, text: 'Olá! Sou a assistente virtual. Como posso ajudar?', sender: 'ia', time: '10:41' },
      { id: 3, text: 'Meu pedido #12345 está atrasado.', sender: 'user', time: '10:42' },
      { id: 4, text: 'Entendido. Verificando o status do pedido #12345... Encontrei aqui, ele está a caminho e a entrega está prevista para hoje às 15h.', sender: 'ia', time: '10:44' },
      { id: 5, text: 'Ótimo, muito obrigado! Resolveu meu problema.', sender: 'user', time: '10:45', status: 'read' },
    ]
  },
  {
    id: 2,
    name: 'Lead Interessado',
    lastMessage: 'Gostaria de um orçamento, por favor.',
    timestamp: '10:30',
    unread: 2,
    avatar: 'https://i.pravatar.cc/150?img=2',
    autoReply: false,
    messages: [
      { id: 1, text: 'Olá, vi seu anúncio e gostaria de mais informações.', sender: 'user', time: '10:28' },
      { id: 2, text: 'Gostaria de um orçamento, por favor.', sender: 'user', time: '10:30', status: 'sent' },
    ]
  },
  {
    id: 3,
    name: 'Suporte Urgente',
    lastMessage: 'Meu sistema está fora do ar!!',
    timestamp: '09:15',
    unread: 0,
    avatar: 'https://i.pravatar.cc/150?img=3',
    autoReply: true,
    messages: []
  },
];

const initialInstances = [
    {
        id: 'instance_1',
        name: 'Vendas Principal',
        number: '+55 11 98765-4321',
        status: 'connected',
        autoReplyGlobal: true,
    },
    {
        id: 'instance_2',
        name: 'Suporte Técnico',
        number: '+55 21 91234-5678',
        status: 'disconnected',
        autoReplyGlobal: false,
    }
];

function App() {
  const [activeView, setActiveView] = useState('chat');
  const [conversations, setConversations] = useState(initialConversations);
  const [selectedConversation, setSelectedConversation] = useState(initialConversations[0]);
  const [messageInput, setMessageInput] = useState('');
  const [instances, setInstances] = useState(initialInstances);
  const [selectedInstance, setSelectedInstance] = useState(initialInstances[0]);
  const [isQrCodeOpen, setIsQrCodeOpen] = useState(false);

  const handleSendMessage = () => {
    if (messageInput.trim() === '') return;

    const newMessage = {
      id: selectedConversation.messages.length + 1,
      text: messageInput,
      sender: 'agent', // Agente/usuário da plataforma
      time: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
      status: 'sent'
    };

    const updatedConversations = conversations.map(conv => {
      if (conv.id === selectedConversation.id) {
        return { ...conv, messages: [...conv.messages, newMessage], lastMessage: messageInput, timestamp: newMessage.time };
      }
      return conv;
    });

    setConversations(updatedConversations);
    setSelectedConversation(prev => ({ ...prev, messages: [...prev.messages, newMessage] }));
    setMessageInput('');
  };

  const getMessageStatusIcon = (status) => {
    switch (status) {
      case 'sent': return <Check className="h-4 w-4 text-gray-500" />;
      case 'delivered': return <CheckCheck className="h-4 w-4 text-gray-500" />;
      case 'read': return <CheckCheck className="h-4 w-4 text-blue-500" />;
      default: return <Clock className="h-4 w-4 text-gray-500" />;
    }
  };

  const ChatInterface = () => (
    <div className="flex h-screen bg-gray-100">
      {/* Sidebar com lista de conversas */}
      <div className="w-1/3 border-r border-gray-200 bg-white flex flex-col">
        <div className="p-4 border-b border-gray-200">
            <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                <Input placeholder="Pesquisar ou começar uma nova conversa" className="pl-10" />
            </div>
        </div>
        <div className="flex-1 overflow-y-auto">
          {conversations.map(conv => (
            <div 
              key={conv.id} 
              className={`flex items-center p-4 cursor-pointer hover:bg-gray-50 ${selectedConversation.id === conv.id ? 'bg-gray-100' : ''}`}
              onClick={() => setSelectedConversation(conv)}
            >
              <img src={conv.avatar} alt={conv.name} className="w-12 h-12 rounded-full mr-4" />
              <div className="flex-1">
                <div className="flex justify-between items-center">
                  <h3 className="font-semibold">{conv.name}</h3>
                  <p className={`text-xs ${conv.unread > 0 ? 'text-green-500' : 'text-gray-500'}`}>{conv.timestamp}</p>
                </div>
                <div className="flex justify-between items-center">
                    <p className="text-sm text-gray-600 truncate">{conv.lastMessage}</p>
                    {conv.unread > 0 && <Badge className="bg-green-500 text-white">{conv.unread}</Badge>}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Área de Chat */}
      <div className="w-2/3 flex flex-col">
        {selectedConversation ? (
          <>
            {/* Cabeçalho do Chat */}
            <div className="flex justify-between items-center p-4 bg-white border-b border-gray-200">
              <div className="flex items-center">
                <img src={selectedConversation.avatar} alt={selectedConversation.name} className="w-10 h-10 rounded-full mr-4" />
                <div>
                    <h2 className="font-semibold">{selectedConversation.name}</h2>
                    <div className="flex items-center text-sm text-gray-500">
                        <span className={`h-2 w-2 rounded-full mr-2 ${selectedInstance.status === 'connected' ? 'bg-green-500' : 'bg-red-500'}`}></span>
                        {selectedInstance.name} - {selectedInstance.number}
                    </div>
                </div>
              </div>
              <div className="flex items-center space-x-2">
                <span className="text-sm text-gray-600">Respostas IA</span>
                <Switch 
                    checked={selectedConversation.autoReply}
                    onCheckedChange={(checked) => {
                        const updatedConversations = conversations.map(c => c.id === selectedConversation.id ? {...c, autoReply: checked} : c);
                        setConversations(updatedConversations);
                        setSelectedConversation(prev => ({...prev, autoReply: checked}));
                    }}
                />
              </div>
            </div>

            {/* Mensagens */}
            <div className="flex-1 p-6 overflow-y-auto bg-gray-100" style={{backgroundImage: "url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png')"}}>
              {selectedConversation.messages.map(msg => (
                <div key={msg.id} className={`flex ${msg.sender === 'user' ? 'justify-start' : 'justify-end'} mb-4`}>
                  <div className={`max-w-lg p-3 rounded-lg ${msg.sender === 'user' ? 'bg-white shadow' : 'bg-green-100 shadow'}`}>
                    {msg.sender === 'ia' && <p className="text-xs font-bold text-green-600 mb-1 flex items-center"><Bot className="h-4 w-4 mr-1"/> Resposta da IA</p>}
                    <p>{msg.text}</p>
                    <div className="flex justify-end items-center mt-1">
                        <p className="text-xs text-gray-500 mr-2">{msg.time}</p>
                        {msg.sender !== 'user' && getMessageStatusIcon(msg.status)}
                    </div>
                  </div>
                </div>
              ))}
            </div>

            {/* Input de Mensagem */}
            <div className="p-4 bg-white border-t border-gray-200">
              <div className="flex items-center space-x-4">
                <Button variant="ghost" size="icon"><Paperclip className="h-5 w-5" /></Button>
                <Input 
                    placeholder="Digite uma mensagem..." 
                    className="flex-1"
                    value={messageInput}
                    onChange={(e) => setMessageInput(e.target.value)}
                    onKeyPress={(e) => e.key === 'Enter' && handleSendMessage()}
                />
                <Button onClick={handleSendMessage}><Send className="h-5 w-5" /></Button>
              </div>
            </div>
          </>
        ) : (
          <div className="flex-1 flex items-center justify-center text-gray-500">
            Selecione uma conversa para começar
          </div>
        )}
      </div>
    </div>
  );

  const SettingsInterface = () => (
    <div className="p-8 max-w-4xl mx-auto">
        <h2 className="text-3xl font-bold mb-6">Configurações</h2>
        <Tabs defaultValue="instances" className="w-full">
            <TabsList>
                <TabsTrigger value="instances">Instâncias</TabsTrigger>
                <TabsTrigger value="ai">Inteligência Artificial</TabsTrigger>
                <TabsTrigger value="account">Minha Conta</TabsTrigger>
            </TabsList>
            <TabsContent value="instances">
                <Card>
                    <CardHeader>
                        <div className="flex justify-between items-center">
                            <div>
                                <CardTitle>Gerenciar Instâncias</CardTitle>
                                <CardDescription>Conecte e gerencie seus números de WhatsApp.</CardDescription>
                            </div>
                            <Button><PlusCircle className="h-4 w-4 mr-2"/> Nova Instância</Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {instances.map(inst => (
                            <div key={inst.id} className="flex items-center justify-between p-4 border rounded-lg mb-4">
                                <div className="flex items-center">
                                    <div className={`h-10 w-10 rounded-full flex items-center justify-center mr-4 ${inst.status === 'connected' ? 'bg-green-100' : 'bg-red-100'}`}>
                                        <MessageCircle className={`h-5 w-5 ${inst.status === 'connected' ? 'text-green-600' : 'text-red-600'}`} />
                                    </div>
                                    <div>
                                        <p className="font-semibold">{inst.name}</p>
                                        <p className="text-sm text-gray-500">{inst.number}</p>
                                    </div>
                                </div>
                                <div className="flex items-center space-x-4">
                                    <div className="flex items-center space-x-2">
                                        <span className="text-sm">Automação Global</span>
                                        <Switch checked={inst.autoReplyGlobal} />
                                    </div>
                                    <Badge className={inst.status === 'connected' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}>
                                        {inst.status === 'connected' ? 'Conectado' : 'Desconectado'}
                                    </Badge>
                                    {inst.status === 'disconnected' && (
                                        <Dialog open={isQrCodeOpen} onOpenChange={setIsQrCodeOpen}>
                                            <DialogTrigger asChild>
                                                <Button variant="outline" size="sm"><QrCode className="h-4 w-4 mr-2"/> Conectar</Button>
                                            </DialogTrigger>
                                            <DialogContent>
                                                <DialogHeader>
                                                    <DialogTitle>Conectar Instância: {inst.name}</DialogTitle>
                                                    <DialogDescription>Escaneie o QR Code com o seu celular para conectar.</DialogDescription>
                                                </DialogHeader>
                                                <div className="flex flex-col items-center justify-center p-4">
                                                    <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" alt="QR Code" className="w-64 h-64" />
                                                    <Button variant="outline" className="mt-4"><RefreshCw className="h-4 w-4 mr-2"/> Gerar Novo QR Code</Button>
                                                </div>
                                            </DialogContent>
                                        </Dialog>
                                    )}
                                    {inst.status === 'connected' && <Button variant="destructive" size="sm"><PowerOff className="h-4 w-4 mr-2"/> Desconectar</Button>}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </TabsContent>
            <TabsContent value="ai">
                <Card>
                    <CardHeader>
                        <CardTitle>Configurações da IA</CardTitle>
                        <CardDescription>Personalize o comportamento da inteligência artificial.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div>
                            <label className="font-medium">Prompt do Sistema</label>
                            <p className="text-sm text-gray-500 mb-2">Este é o prompt principal que define a personalidade e o objetivo do seu assistente de IA.</p>
                            <Textarea 
                                rows={8}
                                defaultValue="Você é um assistente de vendas amigável e prestativo da empresa [NOME DA EMPRESA]. Seu objetivo é responder às perguntas dos clientes, qualificar leads e, se possível, agendar uma demonstração com um vendedor humano. Seja sempre cordial e profissional."
                            />
                        </div>
                        <div>
                            <label className="font-medium">Base de Conhecimento</label>
                            <p className="text-sm text-gray-500 mb-2">Adicione informações sobre sua empresa, produtos e serviços para que a IA possa responder com precisão.</p>
                            <Textarea 
                                rows={12}
                                placeholder="Ex: Nossos planos são: Básico (R$99/mês), Profissional (R$199/mês). Horário de funcionamento: 9h às 18h, de segunda a sexta."
                            />
                        </div>
                        <Button>Salvar Configurações</Button>
                    </CardContent>
                </Card>
            </TabsContent>
            <TabsContent value="account">
                <Card>
                    <CardHeader>
                        <CardTitle>Minha Conta</CardTitle>
                        <CardDescription>Gerencie suas informações pessoais e de faturamento.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Input label="Nome" defaultValue="João Silva" />
                        <Input label="Email" defaultValue="joao@empresa.com" disabled />
                        <Button>Atualizar Informações</Button>
                        <hr/>
                        <h3 className="font-medium">Plano Atual: Professional</h3>
                        <p className="text-sm text-gray-500">Sua próxima fatura será em 15/10/2024.</p>
                        <Button variant="outline">Gerenciar Assinatura</Button>
                    </CardContent>
                </Card>
            </TabsContent>
        </Tabs>
    </div>
  );

  return (
    <div className="flex min-h-screen">
      {/* Main Sidebar */}
      <aside className="w-20 bg-gray-800 text-white flex flex-col items-center py-4 space-y-6">
        <div className="p-2 rounded-lg bg-green-500">
            <MessageCircle className="h-7 w-7" />
        </div>
        <Button variant="ghost" size="icon" onClick={() => setActiveView('chat')} className={`w-12 h-12 ${activeView === 'chat' ? 'bg-gray-700' : ''}`}>
          <Users className="h-6 w-6" />
        </Button>
        <Button variant="ghost" size="icon" onClick={() => setActiveView('settings')} className={`w-12 h-12 ${activeView === 'settings' ? 'bg-gray-700' : ''}`}>
          <Settings className="h-6 w-6" />
        </Button>
        <div className="flex-grow"></div>
        <Button variant="ghost" size="icon" className="w-12 h-12">
          <LogOut className="h-6 w-6" />
        </Button>
      </aside>

      {/* Content */}
      <main className="flex-1">
        {activeView === 'chat' ? <ChatInterface /> : <SettingsInterface />}
      </main>
    </div>
  );
}

export default App;
