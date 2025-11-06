<?php

namespace App\Console\Commands;

use App\AiAgents\ChatAssistantAgent;
use Illuminate\Console\Command;
use LarAgent\Messages\StreamedAssistantMessage;
use function Laravel\Prompts\text;

class ChatConsole extends Command
{
    protected $signature = 'app:chat';
    protected $description = 'Интерактивный чат с AI';

    /**
     * @return void
     */
    public function handle(): void
    {
        $this->info("Добро пожаловать в AI-чат");

        $agent = ChatAssistantAgent::for('chat');

        while (true) {
            // Ввод пользователя
            $userMessage = text(
                label: 'Вы',
                placeholder: 'Введите сообщение...',
                required: true
            );

            // Вывод ответа ассистента
            $this->line("\n🧠 Ассистент:");

            foreach ($agent->respondStreamed($userMessage) as $chunk) {
                if ($chunk instanceof StreamedAssistantMessage) {
                    // Плавный вывод текста
                    $this->slowPrint($chunk->getLastChunk());
                }
            }

            $this->newLine(2); // Отделяем сообщения
            $this->line(str_repeat('─', 50)); // Разделитель
            $this->newLine();
        }
    }

    /**
     * Выводит текст "символ за символом" для эффекта печати
     *
     * @param string $text
     * @param float $delay
     * @return void
     */
    protected function slowPrint(string $text, float $delay = 0.01): void
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($chars as $char) {
            $this->output->write($char);
            usleep((int)($delay * 1_000_000));
        }
    }
}
