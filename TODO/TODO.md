# SIMOVSAT
- BAIXAR TUDO DA WEB (PHP SIMOVSAT) (OK)
---
## FAZER
- SEPARAR 'CHIP' DE 'RASTREADOR'...
- FUNÇÃO  'ESTOQUE' COM DROPDOWN PRA ESCOLHER RASTREADOR E CHIP E DEIXAR EM 'OFF'...
- CRIAR MODELO DE ESTOQUE BASEADO NESSE MODELO. APARTIR DO MODELO DE CHIP E RASTERADOR!
- FUNÇÃO PARA DROPDOWN AO CADASTRAR UM VEICULO.
- VERFICAR FUNÇÃO ADIMPLENTE E INADINPLENTE! Se parou de funcionar ou não!
---

## DETALHES (FALTA SÓ ESSE)
- TODOS OS NOMES PARA PLACA.
- Gráfico precisa contar os que mais rodaram, maior quilometragem da plataforma
- Correção da ordenação da coluna “Duração da parada” – Tela principal Veículo(s) Off-line (SimovSat)
---

FINALIZAR E COBRAR 1200 + 1000;






















## WEB
- ANOTAR O QUE COMBINEI COM ELE PRA FAZER AQUI
- CORREÇÕES ANOTAR AQUI (VALOR 1200) CORREÇÕES KARINA
- CORREÇÕES ANOTAR AQUI (VALOR 1000) CORREÇÕES GILBERTO
---

## REGRAS 
- NO RELATÓRIO MUDAR NOME DO USUARIO PARA PLACA
- LOGO MARCA NA APARECE EM ALGUNS PONTOS DO SISTEMA
- Corrigir ordenação de parada e exibir placa do veículo nos relatórios
---


<!-- 
Problema:
A coluna “Duração da parada” está sendo ordenada de forma incorreta porque está tratada como string (texto). Assim, valores como "126h 46min" ficam abaixo de "9h 3min", pois a ordenação compara os primeiros caracteres e não o tempo real em segundos.

⸻

✅ Regra para correção da ordenação
	1.	Converter os tempos exibidos na coluna “Duração da parada” para segundos totais, de forma invisível ao usuário, para que a ordenação seja feita corretamente.
	2.	Função JavaScript sugerida para conversão:
function parseTimeToSeconds(str) {
  const regex = /(?:(\d+)h)?\s*(?:(\d+)min)?\s*(?:(\d+)s)?/;
  const [, h = 0, m = 0, s = 0] = str.match(regex).map(Number);
  return (h * 3600) + (m * 60) + s;
}
3.	Aplicar essa função no sorting da tabela, seja por:
	•	DataTables.js com data-order oculto;
	•	Vue.js com computed ou sortMethod;
	•	Ou no backend, retornando já o tempo total em segundos no JSON da API como parada_em_segundos.

⸻

🧭 Exemplo de implementação simples no frontend:

Se estiver usando uma lib de tabela como Vue Table, AG Grid ou similar:
rows.sort((a, b) => parseTimeToSeconds(b.duracaoParada) - parseTimeToSeconds(a.duracaoParada));
🎯 Resultado esperado:
Ao aplicar essa regra, a coluna “Duração da parada” será corretamente ordenada do maior tempo para o menor, mesmo que visualmente continue aparecendo no formato "XXh YYmin ZZs". -->
---
