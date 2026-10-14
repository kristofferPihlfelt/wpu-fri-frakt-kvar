# WPU Fri frakt kvar

Gratis plugin från [wordpressutveckling.se](https://wordpressutveckling.se/). Koden förklaras rad för rad i guiden: **[https://wordpressutveckling.se/kvar-till-fri-frakt-woocommerce/](https://wordpressutveckling.se/kvar-till-fri-frakt-woocommerce/)**

Byggt av [Kristoffer Pihlfelt](https://pihlfelt.se) på [Pihlfelt & Vänner](https://pihlfelt.se). Licens: GPL-2.0-or-later.

- **Bidragsgivare:** Pihlfelt & Vänner
- **Kräver WordPress:** 6.7
- **Kräver PHP:** 8.2
- **Kräver:** WooCommerce
- **Version:** 1.0.0
- **Licens:** GPL-2.0-or-later

## Vad det gör

Visar "X kr kvar till fri frakt" eller "Du har fri frakt" med en tunn stapel under. Texten syns i den klassiska varukorgen, i minivarukorgen (widgeten och teman som använder woocommerce_mini_cart()), i den klassiska kassan och i blockvarukorgen och blockkassan. Den uppdateras när kunden ändrar antal, lägger till en rabattkod eller byter adress.

Beloppet läses från fraktmetoden Fri frakt i kundens fraktzon, så du ändrar det på ett ställe: WooCommerce > Inställningar > Frakt. Pluginet följer metodens inställningar: minsta orderbelopp, rabattkoder som ger fri frakt, "tillämpa regeln före rabatt" och om priser visas med eller utan moms i varukorgen. Finns ingen Fri frakt-metod i kundens zon visas ingenting.

Minivarukorgsblocket (Mini-Cart) i blockteman stöds inte.

## Installation

1. Kontrollera att WooCommerce är aktiverat och att kundens fraktzon har metoden Fri frakt med kravet "Ett minsta orderbelopp" eller "Ett minsta orderbelopp ELLER en rabattkod".
2. Gå till Tillägg > Lägg till nytt > Ladda upp tillägg och välj zip-filen. Aktivera.
3. Det finns inga inställningar. Texten kan ändras med filtret wpu_fri_frakt_kvar_message.

## Källa

Koden förklaras i guiden "Visa X kr kvar till fri frakt i WooCommerce":
https://wordpressutveckling.se/kvar-till-fri-frakt-woocommerce/

Den senaste versionen finns alltid på samma adress:
https://wordpressutveckling.se/ladda-ner/wpu-fri-frakt-kvar/

## Ändringslogg

### 1.0.0
* Första versionen, samma kod som i guiden.
