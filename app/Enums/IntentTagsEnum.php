<?php

namespace App\Enums;

enum IntentTagsEnum: string
{
    case REPORT_PROBLEM = 'report_problem';
    case REQUEST_RESOLUTION = 'request_resolution';
    case COMPLAINT = 'complaint';
    case SUGGESTION = 'suggestion';
    case INFORMATION_REQUEST = 'information_request';
    case URGENT_REQUEST = 'urgent_request';
    case MAINTENANCE_REQUEST = 'maintenance_request';
    case SAFETY_ISSUE = 'safety_issue';
    case ENVIRONMENTAL_ISSUE = 'environmental_issue';
    case INFRASTRUCTURE_ISSUE = 'infrastructure_issue';
    case HOUSING_ISSUE = 'housing_issue';
    case TRANSPORT_ISSUE = 'transport_issue';
    case UTILITY_ISSUE = 'utility_issue';
    case CLEANLINESS_ISSUE = 'cleanliness_issue';
    case NOISE_COMPLAINT = 'noise_complaint';
    case PARKING_ISSUE = 'parking_issue';
    case LIGHTING_ISSUE = 'lighting_issue';
    case ROAD_ISSUE = 'road_issue';
    case SIDEWALK_ISSUE = 'sidewalk_issue';
    case GREEN_SPACE_ISSUE = 'green_space_issue';
    case PUBLIC_SERVICE_ISSUE = 'public_service_issue';
    case ADMINISTRATIVE_REQUEST = 'administrative_request';
    case LEGAL_QUESTION = 'legal_question';
    case THANK_YOU = 'thank_you';
    case FOLLOW_UP = 'follow_up';

    public function getLabel(): string
    {
        return match($this) {
            self::REPORT_PROBLEM => 'Сообщение о проблеме',
            self::REQUEST_RESOLUTION => 'Запрос на решение',
            self::COMPLAINT => 'Жалоба',
            self::SUGGESTION => 'Предложение',
            self::INFORMATION_REQUEST => 'Запрос информации',
            self::URGENT_REQUEST => 'Срочный запрос',
            self::MAINTENANCE_REQUEST => 'Запрос на обслуживание',
            self::SAFETY_ISSUE => 'Проблема безопасности',
            self::ENVIRONMENTAL_ISSUE => 'Экологическая проблема',
            self::INFRASTRUCTURE_ISSUE => 'Проблема инфраструктуры',
            self::HOUSING_ISSUE => 'Жилищная проблема',
            self::TRANSPORT_ISSUE => 'Транспортная проблема',
            self::UTILITY_ISSUE => 'Коммунальная проблема',
            self::CLEANLINESS_ISSUE => 'Проблема чистоты',
            self::NOISE_COMPLAINT => 'Жалоба на шум',
            self::PARKING_ISSUE => 'Проблема с парковкой',
            self::LIGHTING_ISSUE => 'Проблема освещения',
            self::ROAD_ISSUE => 'Проблема дороги',
            self::SIDEWALK_ISSUE => 'Проблема тротуара',
            self::GREEN_SPACE_ISSUE => 'Проблема зеленых зон',
            self::PUBLIC_SERVICE_ISSUE => 'Проблема общественных услуг',
            self::ADMINISTRATIVE_REQUEST => 'Административный запрос',
            self::LEGAL_QUESTION => 'Правовой вопрос',
            self::THANK_YOU => 'Благодарность',
            self::FOLLOW_UP => 'Дополнительное обращение',
        };
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getLabels(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->getLabel();
        }
        return $result;
    }
}
