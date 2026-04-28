# PHP-Fullstack-Course

## Данный репозиторий предназначен для хранения работ по дисциплине Fullstack Web Development

### В этом репозитории я буду публиковать домашние задания, а также семестровый проект





DiaryController:
GET /diary?date={date} - получение информации для дневника за этот день(конкретные приемы пищи с содержимым)

MealController:
GET /meals/{id} - получение страницы конкретного продукта
POST /meals/{id} - обновление граммовки/рациона
DELETE /meals/{id} - удаление пищи из приема пищи

ProfileController:
GET /profile - получение страницы профиля
GET /profile/edit - получение формы на редактирования информации о профиле
POST /profile/edit - отправка формы
GET /profile/recalculate - получение формы на пересчет нормы КБЖУ
POST /profile/recalculate - отправка формы

StatisticsController:
GET /stats/weekly - получение статистики за неделю
GET /stats/monthly - получение статистики за месяц

FoodController:
GET /food - получение страница с поисковой строкой
GET /food/{id} - получение страницы конкретного продукта для его добавления в рацион
POST /food/{id} - добавление продукта в рацион
GET /food/search - получение списка пищи по ключевому слову
GET /food/recent -  получение списка недавней пищи
GET /food/add - получение формы для добавления нового рецепта
POST /food/add - отправка данных из формы

FoodCustomController:
GET /food/custom - получение списка своих рецептов пищи
GET /food/custom/{id} получение конкретного рецепта
POST /food/custom/{id} обновление конкретного рецепта
DELETE /food/custom/{id} - удаление рецепта