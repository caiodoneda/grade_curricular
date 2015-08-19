<h1><strong>Grade curricular</strong></h1>

<h2>Configuração dos cursos Moodle</h2>

<h4><strong><p>cursos Moodle optativos</p></strong></h4>
<ul>
	<li><i>Número mínimo de cursos Moodle optativos:</i>
		<p>Número mínimo de cursos optativos que o estudante tem que se inscrever e completar, para que seja aprovado na grade curricular.</p>
	</li>
	<li><i>Número máximo de cursos Moodle optativos:</i>
	    <p>Número máximo de cursos optativos em que o estudante pode se inscrever. Essa informação é utilizada no bloco de inscrições.</p>
	</li>
	<li><i>Selecionar cursos Moodle optativos em bloco:</i>
	</li>	
</ul>

<h4><strong><p>cursos Moodle</p></strong></h4>
<p>Cursos Moodle apresentados em forma de tabela, onde pode-se fazer as suas configurações individuais como:
	<ul>
		<li>Tipo: (obrigatório, optativo, TCC, ou não considerar esse curso)</li>
		<li>Carga horária: Esse campo além de informar a carga horária equivalente a um curso Moodle, ainda é utilizado como critério de envio para o Sistema de Certificados, pois caso seja salvo como zero, o curso não será enviado (independentemente do seu tipo) apesar de, continuar sendo considerado para aprovação ou não de um estudante.</li>
		<li>Período de incrições: Define a data (início e fim) do período de inscrição nesse curso Moodle.</li>
		<li>Pré-requisito: Esse campo é utilizado para definir uma estrutura entre os cursos da Grade curricular, e evita por exemplo, que um estudante se inscreva em um curso que não tenha o pré-requisito cumprido.</li>
	</ul>
</p>

<h2>Configurações adicionais</h2>

<h4><strong><p>Seleção de estudantes</p></strong></h4>

<ul> 
	<li><i>Cohort de estudantes:</i>
		<p>A grade curricular foi concebida para existir independentemente do Sistema de Inscrições e Certificados, e caso esses sistemas não forem utilizados, existe a opção de utilizar esse método para seleção dos estudantes (através de cohorts) que é utlizado como forma de saber em que grupo esses estudantes se encontram. Essa configuração é salva na tabela <i>grade_curricular</i> na coluna <i>studentcohortid</i>.</p> 
	</li>
	<li><i>Atividade no Sistema de Inscrições:</i> 
	    <p>Caso o Sistema de Inscrições seja usado como método de inscrição de pessoas, esse campo irá refletir no conjunto de estudantes correspondentes à essa Grade Curricular. Essa configuração é utilizada em outro plugins, como no plugin local de certificados, que sabe para qual atividade no sistema de inscrições deve enviar os dados uma vez feita essa configuração. O valor desse campo é salvo na tabela <i>grade_curricular</i> na coluna <i>inscricoesactivityid</i>.</p> 
	</li>

	<li>
		Caso nenhuma das seleções acima seja utilizada, os estudantes serão selecionados a partir da categoria onde a Grade Curricular for criada.
	</li>
	
</ul>

<h4><strong><p>Anotações de tutores sobre estudantes</p></strong></h4>

<ul>
	<li><i>curso Moodle onde serão guardadas as anotações dos tutores</i>
		<p>Nesse campo deve-se informar qual curso Moodle, irá armazenar as anotações feitas pelos tutores, com a possibilidade de não salvar em nenhum curso Moodle.</p>
	</li>
	<li><i>Papel de tutor</i>
		<p>Nesse campo deve-se informar qual papel do Moodle corresponde ao papel de Tutor.</p>
	</li>
</ul>