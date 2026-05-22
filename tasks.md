/**
 * 📌 NOTE (Business Rule - Future Improvement):
 *
 * حالياً إغلاق الـ Batch يتم بشكل يدوي من المستخدم (status = closed).
 *
 * لاحقاً سيتم إضافة عمليات:
 * - البيع (Sell)
 * - النفوق (Mortality)
 *
 * وهذه العمليات ستكون مسؤولة عن تقليل current_quantity تلقائياً.
 *
 * وعند وصول current_quantity إلى 0:
 * → سيتم إغلاق الـ Batch تلقائياً (status = closed).
 *
 * حالياً current_quantity قد لا يكون مستخدم بشكل فعلي،
 * لكنه مُجهز للمرحلة القادمة من النظام.
 */

 