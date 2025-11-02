<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted, nextTick, watch, computed } from 'vue'
import MarkdownIt from 'markdown-it'
import markdownItMultiMdTable from 'markdown-it-multimd-table'
import DOMPurify from 'dompurify'
import apiClient from '@/services/api'

// ==================== Types ====================
interface ChatMessage {
  role: 'user' | 'assistant' | 'system'
  content: string
}

interface StreamChunkData {
  delta?: string
  content?: string
  complete?: boolean
  text?: string
  message?: string
  error?: string
}

// ==================== Constants ====================
const API_BASE = import.meta.env.VITE_API_BASE_URL || '/api'
const TOOL_CALL_TIMEOUT = 10000 // 10 секунд ожидания финального ответа после tool call
const SCROLL_THRESHOLD = 100 // Порог для автоскролла (px)
const SCROLL_RESUME_THRESHOLD = 20 // Порог для возобновления автоскролла (px)
const SCROLL_INTERRUPT_THRESHOLD = 120 // Порог для прерывания автоскролла (px)

const ERROR_MESSAGES = {
  GENERIC: 'Упс! что-то пошло не так. Попробуйте еще раз.',
  TOOL_CALL_NO_RESPONSE: '⚠️ Инструмент выполнен, но ответ не получен. Пожалуйста, повторите запрос.',
  STREAM_ERROR: '⚠️ Произошла ошибка при получении ответа.'
} as const

// ==================== State ====================
const messages = reactive<ChatMessage[]>([
  { role: 'assistant', content: 'Привет! Чем могу помочь?' }
])

const userInput = ref('')
const isLoading = ref(false)
const chatMessagesRef = ref<HTMLElement | null>(null)
const isAssistantStreaming = ref(false)
const userInterruptedAutoScroll = ref(false)
const isMinimized = ref(true)
const abortController = ref<AbortController | null>(null)
const historyLoaded = ref(false)
let lastScrollTop = 0

// Markdown renderer with extended table support
const md = new MarkdownIt({
  html: false,
  linkify: true,
  breaks: true,
})

md.use(markdownItMultiMdTable, {
  multiline: true,
  rowspan: true,
  headerless: true,
})

// ==================== Utility Functions ====================
/**
 * Извлекает текст из данных стрима
 */
function extractTextFromStreamData(data: StreamChunkData | string | null): string | null {
  if (!data) return null
  
  if (typeof data === 'string') {
    return data.trim().length > 0 ? data : null
  }
  
  if (typeof data === 'object') {
    return data.content || data.text || data.message || data.delta || null
  }
  
  return null
}

/**
 * Обновляет контент сообщения из данных стрима
 */
function updateMessageContent(
  messageIndex: number,
  data: StreamChunkData
): void {
  const message = messages[messageIndex]
  if (!message) return
  
  // Приоритет: delta > content > text > message
  if (data.delta && data.delta.length > 0) {
    // Инкрементальное обновление через delta (только новая часть)
    message.content += data.delta
    scrollToBottom()
  } else if (data.content && data.content.length > 0) {
    // Полная замена через content (весь текст целиком)
    message.content = data.content
    scrollToBottom()
  } else {
    // Fallback: пытаемся извлечь текст из других полей
    const text = extractTextFromStreamData(data)
    if (text && text.length > 0) {
      message.content += text
      scrollToBottom()
    }
  }
}

/**
 * Отменяет активный запрос и сбрасывает состояние
 */
function cancelRequest(): void {
  if (abortController.value) {
    abortController.value.abort()
    abortController.value = null
  }
  
  isLoading.value = false
  isAssistantStreaming.value = false
}

/**
 * Обрабатывает завершение стрима с возможной задержкой для tool calls
 */
function handleStreamComplete(
  messageIndex: number,
  hasContent: boolean
): void {
  if (!hasContent) {
    // Tool call без контента - ждем финальный ответ
    setTimeout(() => {
      const message = messages[messageIndex]
      if (message && (!message.content || message.content.trim().length === 0)) {
        message.content = ERROR_MESSAGES.TOOL_CALL_NO_RESPONSE
        isLoading.value = false
        isAssistantStreaming.value = false
        scrollToBottom()
      }
    }, TOOL_CALL_TIMEOUT)
  } else {
    // Есть контент - сбрасываем состояние загрузки
    isLoading.value = false
    isAssistantStreaming.value = false
    if (!userInterruptedAutoScroll.value) {
      scrollToBottom()
    }
  }
}

/**
 * Парсит SSE данные из ReadableStream
 */
async function parseSSEStream(
  reader: ReadableStreamDefaultReader<Uint8Array>,
  messageIndex: number
): Promise<void> {
  const decoder = new TextDecoder()
  let buffer = ''
  let currentEvent: string | null = null
  let currentData: string = ''
  
  try {
    while (true) {
      const { done, value } = await reader.read()
      
      if (done) {
        // Обрабатываем оставшиеся данные в буфере
        if (currentData.trim()) {
          processSSEEvent(currentEvent || 'message', currentData.trim(), messageIndex)
        }
        
        isLoading.value = false
        isAssistantStreaming.value = false
        abortController.value = null
        
        // Если контент пустой при закрытии соединения
        const hasContent = !!(messages[messageIndex]?.content && messages[messageIndex].content.trim().length > 0)
        if (!hasContent) {
          messages[messageIndex].content = ERROR_MESSAGES.TOOL_CALL_NO_RESPONSE
        }
        
        if (!userInterruptedAutoScroll.value) {
          scrollToBottom()
        }
        break
      }
      
      buffer += decoder.decode(value, { stream: true })
      const lines = buffer.split('\n')
      buffer = lines.pop() || '' // Оставляем незавершенную строку в буфере
      
      for (const line of lines) {
        // Пустая строка означает конец события
        if (line.trim() === '') {
          if (currentData.trim()) {
            const eventType = currentEvent || 'message'
            processSSEEvent(eventType, currentData.trim(), messageIndex)
          }
          currentEvent = null
          currentData = ''
          continue
        }
        
        // Парсим тип события
        if (line.startsWith('event:')) {
          currentEvent = line.substring(6).trim()
          continue
        }
        
        // Парсим данные (может быть несколько строк data:)
        if (line.startsWith('data:')) {
          const data = line.substring(5)
          // Первая строка данных или добавление к существующим данным
          if (currentData === '') {
            currentData = data
          } else {
            currentData += '\n' + data
          }
          continue
        }
        
        // Игнорируем другие типы строк (id:, retry: и т.д.)
      }
    }
  } catch (err: any) {
    // Игнорируем ошибки отмены запроса
    if (err.name === 'AbortError') {
      return
    }
    
    console.error('Error reading stream:', err)
    // При ошибке показываем сообщение об ошибке
    const last = messages[messages.length - 1]
    if (last && last.role === 'assistant') {
      if (!last.content) {
        last.content = ERROR_MESSAGES.GENERIC
      } else {
        last.content += `\n\n${ERROR_MESSAGES.STREAM_ERROR}`
      }
    } else {
      messages.push({ role: 'assistant', content: ERROR_MESSAGES.GENERIC })
    }
    cancelRequest()
    throw err
  }
}

/**
 * Обрабатывает одно SSE событие
 * Не закрывает соединение - только обновляет контент и состояние
 */
function processSSEEvent(eventType: string, data: string, messageIndex: number): void {
  try {
    const parsedData: StreamChunkData = JSON.parse(data)
    
    // Обрабатываем ошибки от сервера
    if (parsedData.error) {
      const last = messages[messages.length - 1]
      if (last && last.role === 'assistant') {
        if (!last.content) {
          last.content = `Ошибка: ${parsedData.error}`
        } else {
          last.content += `\n\n⚠️ Ошибка: ${parsedData.error}`
        }
      }
      cancelRequest()
      return
    }
    
    // Обновляем контент сообщения
    if (parsedData.delta || parsedData.content || parsedData.text || parsedData.message) {
      updateMessageContent(messageIndex, parsedData)
    }
    
    // Если complete: true, сбрасываем состояние загрузки
    if (parsedData.complete === true) {
      const hasContent = !!(messages[messageIndex]?.content && messages[messageIndex].content.trim().length > 0)
      handleStreamComplete(messageIndex, hasContent)
    }
  } catch (err) {
    // Если не JSON, добавляем как текст
    if (data.trim().length > 0) {
      messages[messageIndex].content += data
      scrollToBottom()
    }
  }
}

const renderMarkdown = (text: string): string => {
  try {
    const html = md.render(String(text || ''))
    return DOMPurify.sanitize(html)
  } catch {
    return DOMPurify.sanitize(String(text || ''))
  }
}

const scrollToBottom = async (force: boolean = false): Promise<void> => {
  await nextTick()
  const el = chatMessagesRef.value
  if (!el) return
  const distanceFromBottom = el.scrollHeight - el.scrollTop - el.clientHeight
  // Если пользователь прокручивает вверх во время стрима ассистента — не скроллим до нового сообщения пользователя
  if (!force && isAssistantStreaming.value && userInterruptedAutoScroll.value) return
  if (force || distanceFromBottom < SCROLL_THRESHOLD) {
    el.scrollTop = el.scrollHeight
  }
}

function onMessagesScroll(): void {
  const el = chatMessagesRef.value
  if (!el) return
  const distanceFromBottom = el.scrollHeight - el.scrollTop - el.clientHeight
  const scrolledUp = el.scrollTop < lastScrollTop
  lastScrollTop = el.scrollTop
  // Считаем, что пользователь «читает вверх», если двигается наверх ИЛИ ушёл дальше порога
  if (isAssistantStreaming.value) {
    if (scrolledUp || distanceFromBottom > SCROLL_INTERRUPT_THRESHOLD) {
      userInterruptedAutoScroll.value = true
    }
    // Если пользователь вернулся к низу — возобновляем автоскролл
    if (distanceFromBottom <= SCROLL_RESUME_THRESHOLD) {
      userInterruptedAutoScroll.value = false
    }
  }
}

function onMessagesWheel(e: WheelEvent): void {
  // Любой прокрут вверх колесом во время стрима отключает автоскролл
  if (isAssistantStreaming.value && e.deltaY < 0) {
    userInterruptedAutoScroll.value = true
  }
  // Если крутим вниз и почти у низа — включаем автоскролл обратно
  const el = chatMessagesRef.value
  if (isAssistantStreaming.value && e.deltaY > 0 && el) {
    const distanceFromBottom = el.scrollHeight - el.scrollTop - el.clientHeight
    if (distanceFromBottom <= SCROLL_RESUME_THRESHOLD) {
      userInterruptedAutoScroll.value = false
    }
  }
}

const sendMessage = async (): Promise<void> => {
  const text = userInput.value.trim()
  if (!text || isLoading.value) return
  
  // Отменяем предыдущий запрос, если он есть
  cancelRequest()
  
  messages.push({ role: 'user', content: text })
  userInput.value = ''
  isLoading.value = true
  await scrollToBottom(true)

  try {
    // Всегда стриминг
    messages.push({ role: 'assistant', content: '' })
    const aiIndex = messages.length - 1
    isAssistantStreaming.value = true
    userInterruptedAutoScroll.value = false
    
    // Сразу скроллим к новому сообщению "Думает..."
    await scrollToBottom(true)
    
    // Создаем AbortController для возможности отмены запроса
    const controller = new AbortController()
    abortController.value = controller
    
    // Отправляем POST запрос с телом
    // История управляется бэкендом через сессию
    const response = await fetch(`${API_BASE}/v1/chat`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'text/event-stream',
      },
      credentials: 'include', // Включаем отправку cookies для работы сессий
      body: JSON.stringify({
        message: text
      }),
      signal: controller.signal
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    if (!response.body) {
      throw new Error('Response body is null')
    }
    
    // Читаем стрим
    const reader = response.body.getReader()
    await parseSSEStream(reader, aiIndex)
    
  } catch (e: any) {
    // Игнорируем ошибки отмены запроса
    if (e.name === 'AbortError') {
      return
    }
    
    console.error('Error sending message:', e)
    const last = messages[messages.length - 1]
    if (last && last.role === 'assistant' && !last.content) {
      last.content = ERROR_MESSAGES.GENERIC
    } else {
      messages.push({ role: 'assistant', content: ERROR_MESSAGES.GENERIC })
    }
    isLoading.value = false
    isAssistantStreaming.value = false
  }
}

const toggleMinimize = (): void => {
  isMinimized.value = !isMinimized.value
  if (!isMinimized.value) {
    nextTick(() => {
      scrollToBottom()
    })
  }
}

const handleKeydown = (e: KeyboardEvent): void => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    sendMessage()
  }
}

/**
 * Извлекает текст из content, который может быть строкой, массивом или объектом
 */
function extractContentText(content: any): string {
  if (!content) {
    return ''
  }
  
  if (typeof content === 'string') {
    return content
  }
  
  if (Array.isArray(content)) {
    // Если content - массив (например, [{type: 'text', text: '...'}])
    return content
      .map((item: any) => {
        if (typeof item === 'string') {
          return item
        }
        if (item && typeof item === 'object') {
          return item.text || item.content || ''
        }
        return String(item || '')
      })
      .filter((text: string) => text.trim().length > 0)
      .join('')
  }
  
  if (typeof content === 'object') {
    // Если content - объект
    return content.text || content.content || String(content)
  }
  
  return String(content || '')
}

/**
 * Загружает историю чата из API и обновляет сообщения
 */
async function loadChatHistory(): Promise<void> {
  // Загружаем историю только один раз
  if (historyLoaded.value) {
    return
  }

  try {
    const response = await apiClient.getChatHistory()
    
    if (response.history && response.history.length > 0) {
      // Очищаем текущие сообщения и загружаем историю
      messages.length = 0
      
      response.history.forEach((msg: any) => {
        // Пропускаем сообщения с tool_calls и пустым content
        // Это промежуточные сообщения от ассистента, которые только вызывают инструмент
        if (msg.tool_calls && msg.tool_calls.length > 0) {
          const contentText = extractContentText(msg.content)
          // Пропускаем только если content пустой
          if (!contentText || contentText.trim().length === 0) {
            return
          }
        }
        
        // Извлекаем текст из content
        const content = extractContentText(msg.content)
        
        // Пропускаем сообщения с пустым content
        if (!content || content.trim().length === 0) {
          return
        }
        
        messages.push({
          role: msg.role as 'user' | 'assistant',
          content: content
        })
      })
      
      // Если после фильтрации не осталось сообщений, показываем приветственное
      if (messages.length === 0) {
        messages.push({ role: 'assistant', content: 'Привет! Чем могу помочь?' })
      }
      
      // Скроллим вниз после загрузки истории
      await nextTick()
      scrollToBottom()
    }
    
    historyLoaded.value = true
  } catch (error) {
    console.error('Failed to load chat history:', error)
    // Не помечаем как загруженную, чтобы можно было повторить попытку
  }
}

/**
 * Проверяет, есть ли история чата (не считая приветственное сообщение)
 */
const hasHistory = computed(() => {
  const userAssistantMessages = messages.filter(m => m.role !== 'system')
  // Есть история, если больше одного сообщения или есть сообщение от пользователя
  return userAssistantMessages.length > 1 || 
         (userAssistantMessages.length === 1 && userAssistantMessages[0].role === 'user')
})

/**
 * Очищает историю чата
 */
async function clearChat(): Promise<void> {
  try {
    await apiClient.clearChatHistory()
    
    // Очищаем сообщения и показываем приветственное сообщение
    messages.length = 0
    messages.push({ role: 'assistant', content: 'Привет! Чем могу помочь?' })
    
    // Сбрасываем флаг загрузки истории, чтобы при следующем открытии она загрузилась заново
    historyLoaded.value = false
    
    await nextTick()
    scrollToBottom()
  } catch (error) {
    console.error('Failed to clear chat history:', error)
  }
}

onMounted(() => {
  scrollToBottom()
  chatMessagesRef.value?.addEventListener('scroll', onMessagesScroll, { passive: true })
  chatMessagesRef.value?.addEventListener('wheel', onMessagesWheel, { passive: true })
  
  // Загружаем историю при монтировании компонента
  loadChatHistory()
})

// Загружаем историю при открытии чата, если она еще не была загружена
watch(isMinimized, (newValue) => {
  if (!newValue && !historyLoaded.value) {
    loadChatHistory()
  }
})

onUnmounted(() => {
  // Отменяем активные запросы при размонтировании
  cancelRequest()
  chatMessagesRef.value?.removeEventListener('scroll', onMessagesScroll)
  chatMessagesRef.value?.removeEventListener('wheel', onMessagesWheel)
})
</script>

<template>
  <div class="chat-widget" :class="{ minimized: isMinimized }">
    <!-- Кнопка для открытия чата -->
    <button v-if="isMinimized" class="chat-toggle-btn" @click="toggleMinimize" aria-label="Открыть чат">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
      </svg>
    </button>

    <!-- Окно чата -->
    <div v-else class="chat-window">
      <div class="chat-header">
        <div class="chat-header-left">
          <div class="model-pill">AI ассистент</div>
        </div>
        <div class="chat-header-right">
          <button 
            v-if="hasHistory" 
            class="chat-header-btn" 
            @click="clearChat" 
            aria-label="Очистить историю чата"
            title="Очистить историю"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
          </button>
          <button class="chat-header-btn" @click="toggleMinimize" aria-label="Свернуть чат">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 6L6 18M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>

      <div ref="chatMessagesRef" class="chat-messages" aria-live="polite" @wheel.stop @touchmove.stop>
        <div 
          v-for="(m, i) in messages.filter(m => m.role !== 'system')" 
          :key="i" 
          class="message"
          :class="m.role"
        >
          <div class="message-avatar">
            <template v-if="m.role === 'assistant'">
              <div class="avatar-icon">🤖</div>
            </template>
            <template v-else>
              <div class="avatar-icon">👤</div>
            </template>
          </div>
          <div class="message-bubble">
            <template v-if="m.role === 'assistant' && !m.content">
              <div class="typing">
                <span class="typing-label">Думает</span>
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
              </div>
            </template>
            <div v-else class="table-wrapper" v-html="renderMarkdown(m.content)"></div>
          </div>
        </div>
      </div>

      <div class="chat-input">
        <textarea
          v-model="userInput"
          :disabled="isLoading"
          placeholder="Напишите сообщение..."
          rows="2"
          class="input"
          @keydown="handleKeydown"
          @wheel.stop
          @touchmove.stop
        />
        <button class="btn btn-primary btn-md" :disabled="isLoading || !userInput.trim()" @click="sendMessage">
          {{ isLoading ? 'Отправка...' : 'Отправить' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.chat-widget {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.chat-widget.minimized .chat-window {
  display: none;
}

.chat-toggle-btn {
  @apply w-14 h-14 rounded-full bg-primary-600 text-white cursor-pointer flex items-center justify-center shadow-lg transition-all duration-200 hover:bg-primary-700 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2;
}

.chat-window {
  @apply w-96 max-w-[calc(100vw-40px)] h-[600px] max-h-[calc(100vh-40px)] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg flex flex-col overflow-hidden;
}

.chat-header {
  @apply flex justify-between items-center px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex-shrink-0;
}

.chat-header-left {
  @apply flex items-center gap-2;
}

.chat-header-right {
  @apply flex items-center gap-2;
}

.chat-header-btn {
  @apply bg-transparent border-none text-gray-500 dark:text-gray-400 cursor-pointer p-1 flex items-center justify-center rounded-md transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none;
}

.model-pill {
  @apply text-xs font-medium px-2 py-1 rounded-full border-transparent bg-primary-100 text-primary-900 dark:bg-primary-900 dark:text-primary-100;
}

.chat-messages {
  @apply flex-1 overflow-auto p-4 flex flex-col gap-3 overscroll-contain min-h-0;
}

.message {
  @apply grid grid-cols-[36px_1fr] gap-3 items-start;
}

.message.user .message-bubble {
  @apply bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600;
}

.message.assistant .message-bubble {
  @apply bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800;
}

.message-avatar {
  @apply w-10 h-10 grid place-items-center rounded-lg bg-transparent border border-gray-200 dark:border-gray-700 overflow-hidden;
}

.avatar-icon {
  @apply text-2xl leading-none;
}

.message-bubble {
  @apply px-4 py-3 rounded-lg whitespace-pre-wrap overflow-hidden break-words text-gray-900 dark:text-gray-100;
}

.typing {
  @apply inline-flex items-center gap-1.5 opacity-85;
}
.typing-label {
  @apply text-sm;
}
.dot {
  @apply w-1.5 h-1.5 rounded-full bg-primary-600 dark:bg-primary-400 inline-block;
  animation: blink 1.4s infinite both;
}
.dot:nth-child(2) { animation-delay: 0.2s; }
.dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes blink {
  0%, 80%, 100% { opacity: 0.2; transform: translateY(0); }
  40% { opacity: 1; transform: translateY(-2px); }
}

/* Markdown content tuning */
.message-bubble :where(p) { margin: 0.25rem 0; }
.message-bubble :where(ul, ol) {
  margin: 0.25rem 0 0.25rem 0;
  padding-left: 1.2rem;
}
.message-bubble li { margin: 0.25rem 0; }
.message-bubble :where(h1, h2, h3, h4, h5, h6) {
  margin: 0.5rem 0 0.35rem;
  line-height: 1.25;
}
.message-bubble h1 { font-size: 1.35rem; }
.message-bubble h2 { font-size: 1.25rem; }
.message-bubble h3 { font-size: 1.15rem; }
.message-bubble :where(code) {
  @apply bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 rounded;
}
.message-bubble pre {
  @apply bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-3 rounded-lg overflow-auto whitespace-pre-wrap break-words;
}
.message-bubble pre code {
  @apply bg-transparent p-0;
}
.message-bubble a {
  @apply text-primary-600 dark:text-primary-400 underline break-words;
}

.message-bubble img {
  @apply max-w-full h-auto rounded-lg;
}
.message-bubble :deep(table) {
  @apply w-full border-collapse table-auto border border-gray-300 dark:border-gray-600;
}
.message-bubble :deep(th), .message-bubble :deep(td) {
  @apply border border-gray-300 dark:border-gray-600 px-3 py-2 text-left align-top;
}
.message-bubble :deep(thead th) {
  @apply bg-gray-100 dark:bg-gray-700 font-semibold;
}
.message-bubble :deep(tbody tr:nth-child(odd)) {
  @apply bg-gray-50 dark:bg-gray-800/50;
}
.message-bubble .table-wrapper {
  @apply w-full overflow-x-auto;
}
.message-bubble blockquote {
  @apply border-l-4 border-primary-500 dark:border-primary-400 my-2 pl-3 opacity-90;
}

.chat-input {
  @apply grid grid-cols-[1fr_auto] gap-3 items-end border-t border-gray-200 dark:border-gray-700 p-3 bg-white dark:bg-gray-800 flex-shrink-0;
}

.chat-input textarea {
  @apply resize-y min-h-[72px];
}

@media (max-width: 768px) {
  .chat-widget {
    @apply bottom-2.5 right-2.5;
  }
  
  .chat-window {
    @apply w-[calc(100vw-20px)] h-[calc(100vh-20px)] max-h-[calc(100vh-20px)];
  }
  
  .chat-input {
    @apply grid-cols-1 gap-2;
  }
  
  .chat-input .btn {
    @apply w-full;
  }
  
  .chat-input textarea {
    @apply min-h-20 text-base leading-snug; /* избегаем авто-скейлинга на iOS */
  }
}

@media (max-width: 480px) {
  .chat-widget {
    @apply bottom-0 right-0;
  }
  
  .chat-window {
    @apply w-screen h-screen max-h-screen rounded-none border-l-0 border-r-0 border-b-0;
  }
  
  .chat-messages {
    @apply px-1 py-3;
  }
  
  .message-bubble {
    @apply px-3 py-2;
  }
  
  .message {
    @apply gap-2;
  }
  
  .message-avatar {
    @apply w-9 h-9;
  }
}
</style>


