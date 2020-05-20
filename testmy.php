 <?php
 /**
  * Created by PhpStorm.
  * User: AlexxWide
  * Date: 29.04.20
  * Time: 19:00
  */
 
 namespace Classes\Dashboard\Widgets\BudgetCountries;
 
 use Classes\Dashboard\AbstractWidget;
 use Classes\Dashboard\WidgetRenderer;
 use Classes\Money\Entity\Currency;
 use \CRM;
 use Classes\helpers\CurrencyConverter;
 
 class BudgetCountries extends AbstractWidget
 {
 
     /**
      * Наименование виджета
      * @return string
      */
     public function getName()
     {
         return 'Бюджет по странам';
     }
 
     /**
      * Создания непосредственно представления данного виджета
      *
      * @return string
      * @throws \Exception
      * @throws \Throwable
      */
     public function getContent() {
         $cacheKey = 'BudgetCountries' . date('Ym');
         if (($renderData = CRM::cacheGet($cacheKey)) === false) {
 //            $fromDate = date('2020-mm');
 //            $toDate = date('YY-mm-tt');
             $result = [];
             $budget2 = CRM::dbMain()->queryQuick("
         SELECT country.id as id_country, ocr.shortname as shortname, country.name, SUM(budget.amount) as total,
          if (budget.id_currency in (0,1), 1, budget.id_currency) as budget_currency, country.id_currency as country_currency
         FROM obj_countries country
         LEFT JOIN obj_inc_bills_customs custom on (country.id=custom.id_country)
         LEFT JOIN obj_inc_bills_budget budget on (custom.id=budget.id_custom)
         LEFT JOIN obj_currency ocr on (country.id_currency=ocr.id)
         WHERE budget.month >= '" . date('Y.m') . "'
         /*WHERE budget.month >= '" . $fromDate . "' AND budget.month <= '" . $toDate . "'*/
         GROUP BY budget_currency, id_country
         ORDER BY country.id ASC")->assoc();
 ////            бюджет без учета валют
             pp($budget2);
             foreach ($budget2 as $item) {
                 $result[$item['id_country']]['name'] = $item['name'];
                 $result[$item['id_country']]['result3'] = $item['shortname'];
                 $result[$item['id_country']]['result'] +=
                     CurrencyConverter::convertCurrencyAmount($item['total'],
                         Currency::getById($item['budget_currency']),
                         Currency::getById($item['country_currency']));
             }
 //            бюджет с учетом валют
             pp($result);
 //            $fromDate2 = date('2011-m-01 00:00:00');
 //            $toDate2 = date('Y-m-t 23:59:59');
             $payedMoneyAll2 = CRM::dbMain()->queryQuick("
         SELECT SUM(oib.payed_money) as total2, oib.type_currency_custom, oc.name as country_name, oc.id as id_country , oc.id_currency as id_currency, oib.type_currency as type_currency
         FROM obj_countries oc
         JOIN obj_inc_bills_customs oibc on (oibc.id_country=oc.id)
         LEFT JOIN obj_inc_bills oib  on (oib.id_pay_code=oibc.id)
         WHERE oib.status = 'payed'
         AND oib.pay_date >= '" . date('Y.m') . "'
         /*AND oib.pay_date >= '" . $fromDate2 . "' AND oib.pay_date <= '" . $toDate2 . "'*/
         GROUP BY type_currency, country_name
         ORDER BY country_name ASC")->assoc();
 //            расходы без учета валют
             pp($payedMoneyAll2);
             foreach ($payedMoneyAll2 as $item) {
 //            расходы с учетом валют
                 $result[$item['id_country']]['result2'] +=
                     CurrencyConverter::convertCurrencyAmount($item['total2'],
                         Currency::getById($item['type_currency']),
                         Currency::getById($item['id_currency']));
             }
 //            весь бюджет и все расходы с учетом валют
             pp($result);
             $renderData = [
                 'periodFrom' => date('Y-m-01'),
                 'periodTo' => date('Y-m-t'),
                 'budget' => $result,
             ];
             CRM::cacheSet($cacheKey, $renderData, 60);
         }
         return WidgetRenderer::render('BudgetCountries/views/widget.twig', $renderData);
     }
     public function isWidgetAllowedByRight()
     {
         return \CRM::UserAllowed('test');
     }
 }
