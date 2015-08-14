<h1><strong>Grade curricular</strong></h1>

<h2>Configuração dos cursos Moodle</h2>

<h4><strong><p>cursos Moodle optativos</p></strong></h4>
<ul>
	<li><i>Número mínimo de cursos Moodle optativos:</i>
		<p>Número mínimo de cursos optativos que o estudante tem que se inscrever e ser aprovado, para que seja aprovado na grade curricular.</p>
	</li>
	<li><i>Número máximo de cursos Moodle optativos:</i>
	    <p>Número máximo de cursos optativos em que o estudante pode se inscrever. Essa informação é utilizada em outro plugin, o bloco de inscrições.</p>
	</li>
	<li><i>Selecionar cursos Moodle optativos em bloco:</i>
	</li>	
</ul>

<h4><strong><p>cursos Moodle</p></strong></h4>
<p>Cursos Moodle apresentados em forma de tabela, onde pode-se fazer as suas configurações individuais como:
	<ul>
		<li>Tipo: (obrigatório, optativo, TCC, ou não considerar esse curso)</li>
		<li>Carga horária: Esse campo além de informar a carga horária equivalente a um curso Moodle, ainda é utilizado como critério de envio para o Sistema de Certificados, pois caso seja salvo como zero, o curso não será enviado (independentemente do seu tipo) apesar de, continuar sendo considerado para aprovação ou não de um estudante.</li>
		<li>Período de incrições: Define a data (início e fim) do período de inscrição nesse cursos Moodle.</li>
		<li>Pré-requisito: Esse campo é utilizado para definir uma amarração entre os cursos da Grade curricular, e não deixar um estudante se inscrever em um curso que exija outro como pré-requisito.</li>
	</ul>
</p>

<h2>Configurações adicionais</h2>

<h4><strong><p>Seleção de estudantes</p></strong></h4>

<ul> 
	<li><i>Atividade no Sistema de Inscrições:</i> 
	    <p>Essa configuração é necessária sempre que se quiser utilizar o Sistema de Inscrições como método de inscrição de pessoas, junto com o Sistema de Certificados para certificação. Uma vez salva essa configuração, o plugin local de certificados sabe para qual atividade no sistema de inscrições deve enviar os dados. Essa configuração é salva na tabela <i>grade_curricular</i> na coluna <i>inscricoesactivityid</i>.</p> 
	</li>
	<li><i>Cohort de estudantes:</i>
		<p>A grade curricular foi concebida para existir independentemente do Sistema de Inscrições e Certificados, e caso esses sistemas não forem utilizados, existe a opção de utilizar esse outro método para seleção dos estudantes (através de cohorts) que é utlizado no bloco de inscrições como forma de saber em que grupo esses estudantes se encontram. Essa configuração é salva na tabela <i>grade_curricular</i> na coluna <i>studentcohortid</i>.</p> 
	</li>
</ul>

<h4><strong><p>Anotações de tutores sobre estudantes</p></strong></h4>

<ul>
	<li>curso Moodle onde serão guardadas as anotações dos tutores</li>
	<li>Papel de tutor</li>
</ul>