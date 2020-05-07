<?php

namespace NFePHP\NFSeGinfes\Common;

/**
 * Class for RPS XML convertion
 *
 * @category  NFePHP
 * @package   NFePHP\NFSeBetha
 * @copyright NFePHP Copyright (c) 2020
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @author    Cleiton Perin <cperin20 at gmail dot com>
 * @link      http://github.com/nfephp-org/sped-nfse-betha for the canonical source repository
 */

use DOMNode;
use NFePHP\Common\DOMImproved as Dom;
use stdClass;

class Factory
{

    /**
     * @var stdClass
     */
    protected $std;

    /**
     * @var Dom
     */
    protected $dom;

    /**
     * @var DOMNode
     */
    protected $rps;

    /**
     * @var \stdClass
     */
    protected $config;

    /**
     * Constructor
     * @param stdClass $std
     */
    public function __construct(stdClass $std)
    {
        $this->std = $std;

        $this->dom = new Dom('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
        $this->rps = $this->dom->createElement('Rps');
    }

    /**
     * Add config
     * @param \stdClass $config
     */
    public function addConfig($config)
    {
        $this->config = $config;
    }

    /**
     * Builder, converts sdtClass Rps in XML Rps
     * NOTE: without Prestador Tag
     * @return string RPS in XML string format
     */
    public function render()
    {
        $infRps = $this->dom->createElement('InfRps');
        $this->addIdentificacao($infRps);

        $this->dom->addChild(
            $infRps,
            "DataEmissao",
            $this->std->dataemissao,
            true
        );
        $this->dom->addChild(
            $infRps,
            "NaturezaOperacao",
            $this->std->naturezaoperacao,
            true
        );
        $this->dom->addChild(
            $infRps,
            "RegimeEspecialTributacao",
            $this->std->regimeespecialtributacao,
            false
        );
        $this->dom->addChild(
            $infRps,
            "OptanteSimplesNacional",
            $this->std->optantesimplesnacional,
            true
        );
        $this->dom->addChild(
            $infRps,
            "IncentivadorCultural",
            $this->std->incentivadorcultural,
            true
        );
        $this->dom->addChild(
            $infRps,
            "Status",
            $this->std->status,
            true
        );

        $this->addRpsSubstituido($infRps);
        $this->addServico($infRps);
        $this->addPrestador($infRps);
        $this->addTomador($infRps);
        $this->addIntermediario($infRps);
        $this->addOrgaoGerador($infRps);
        $this->addConstrucao($infRps);

        $this->rps->appendChild($infRps);
        $this->dom->appendChild($this->rps);
        return str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $this->dom->saveXML());
    }

    /**
     * Includes Identificacao TAG in parent NODE
     * @param DOMNode $parent
     */
    protected function addIdentificacao(&$parent)
    {
        if (empty($this->std->identificacaorps)) {
            return;
        }
        $id = $this->std->identificacaorps;
        $node = $this->dom->createElement('IdentificacaoRps');
        $this->dom->addChild(
            $node,
            "Numero",
            $id->numero,
            true
        );
        $this->dom->addChild(
            $node,
            "Serie",
            $id->serie,
            true
        );
        $this->dom->addChild(
            $node,
            "Tipo",
            $id->tipo,
            true
        );
        $parent->appendChild($node);
    }

    /**
     * Includes RpsSubstituido TAG in parent NODE
     * @param DOMNode $parent
     */
    protected function addRpsSubstituido(&$parent)
    {
        if (empty($this->std->rpssubstituido)) {
            return;
        }
        $id = $this->std->rpssubstituido;
        $node = $this->dom->createElement('RpsSubstituido');
        $this->dom->addChild(
            $node,
            "Numero",
            $id->numero,
            true
        );
        $this->dom->addChild(
            $node,
            "Serie",
            $id->serie,
            true
        );
        $this->dom->addChild(
            $node,
            "Tipo",
            $id->tipo,
            true
        );
        $parent->appendChild($node);
    }

    /**
     * Includes prestador
     * @param DOMNode $parent
     * @return void
     */
    protected function addPrestador(&$parent)
    {
        if (!isset($this->config)) {
            return;
        }
        $node = $this->dom->createElement('Prestador');
        $this->dom->addChild(
            $node,
            "Cnpj",
            !empty($this->config->cnpj) ? $this->config->cnpj : null,
            false
        );
        $this->dom->addChild(
            $node,
            "InscricaoMunicipal",
            $this->config->im,
            true
        );
        $parent->appendChild($node);
    }

    /**
     * Includes Servico TAG in parent NODE
     * @param DOMNode $parent
     */
    protected function addServico(&$parent)
    {
        $serv = $this->std->servico;
        $val = $this->std->servico->valores;
        $node = $this->dom->createElement('Servico');
        $valnode = $this->dom->createElement('Valores');
        $this->dom->addChild(
            $valnode,
            "ValorServicos",
            number_format($val->valorservicos, 2, '.', ''),
            true
        );
        $this->dom->addChild(
            $valnode,
            "ValorDeducoes",
            isset($val->valordeducoes) ? number_format($val->valordeducoes, 2, '.', '') : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "ValorPis",
            isset($val->valorpis) ? number_format($val->valorpis, 2, '.', '') : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "ValorCofins",
            isset($val->valorcofins) ? number_format($val->valorcofins, 2, '.', '') : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "ValorInss",
            isset($val->valorinss) ? number_format($val->valorinss, 2, '.', '') : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "ValorIr",
            isset($val->valorir) ? number_format($val->valorir, 2, '.', '') : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "ValorCsll",
            isset($val->valorcsll) ? number_format($val->valorcsll, 2, '.', '') : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "IssRetido",
            isset($val->issretido) ? $val->issretido : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "ValorIss",
            isset($val->valoriss) ? number_format($val->valoriss, 2, '.', '') : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "ValorIssRetido",
            isset($val->valorissretido) ? $val->valorissretido : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "OutrasRetencoes",
            isset($val->outrasretencoes) ? number_format($val->outrasretencoes, 2, '.', '') : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "BaseCalculo",
            isset($val->basecalculo)
                ? number_format($val->basecalculo, 2, '.', '')
                : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "Aliquota",
            isset($val->aliquota) ? $val->aliquota : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "ValorLiquidoNfse",
            isset($val->valorliquidonfse) ? $val->valorliquidonfse : null,
            true
        );
        $this->dom->addChild(
            $valnode,
            "DescontoIncondicionado",
            isset($val->descontoincondicionado) ? number_format($val->descontoincondicionado, 2, '.', '') : null,
            false
        );
        $this->dom->addChild(
            $valnode,
            "DescontoCondicionado",
            isset($val->descontocondicionado) ? number_format($val->descontocondicionado, 2, '.', '') : null,
            false
        );
        $node->appendChild($valnode);
        $this->dom->addChild(
            $node,
            "ItemListaServico",
            $serv->itemlistaservico,
            true
        );
        $this->dom->addChild(
            $node,
            "CodigoCnae",
            isset($serv->codigocnae) ? $serv->codigocnae : null,
            false
        );
        $this->dom->addChild(
            $node,
            "CodigoTributacaoMunicipio",
            isset($serv->codigotributacaomunicipio) ? $serv->codigotributacaomunicipio : null,
            false
        );
        $this->dom->addChild(
            $node,
            "Discriminacao",
            $serv->discriminacao,
            true
        );
        $this->dom->addChild(
            $node,
            "CodigoMunicipio",
            $serv->codigomunicipio,
            true
        );
        $parent->appendChild($node);
    }

    /**
     * Includes Tomador TAG in parent NODE
     * @param DOMNode $parent
     */
    protected function addTomador(&$parent)
    {
        $node = $this->dom->createElement('Tomador');
        if (!isset($this->std->tomador)) {
            return $parent->appendChild($node);
        }
        $tom = $this->std->tomador;
        $ide = $this->dom->createElement('IdentificacaoTomador');
        $cpfcnpj = $this->dom->createElement('CpfCnpj');
        if (isset($tom->cnpj)) {
            $this->dom->addChild(
                $cpfcnpj,
                "Cnpj",
                $tom->cnpj,
                true
            );
        } else {
            $this->dom->addChild(
                $cpfcnpj,
                "Cpf",
                $tom->cpf,
                true
            );
        }
        $ide->appendChild($cpfcnpj);
        $this->dom->addChild(
            $ide,
            "InscricaoMunicipal",
            isset($tom->inscricaomunicipal) ? $tom->inscricaomunicipal : null,
            false
        );
        $node->appendChild($ide);
        $this->dom->addChild(
            $node,
            "RazaoSocial",
            $tom->razaosocial,
            true
        );
        if (!empty($this->std->tomador->endereco)) {
            $end = $this->std->tomador->endereco;
            $endereco = $this->dom->createElement('Endereco');
            $this->dom->addChild(
                $endereco,
                "Endereco",
                $end->endereco,
                true
            );
            $this->dom->addChild(
                $endereco,
                "Numero",
                $end->numero,
                true
            );
            $this->dom->addChild(
                $endereco,
                "Complemento",
                isset($end->complemento) ? $end->complemento : null,
                false
            );
            $this->dom->addChild(
                $endereco,
                "Bairro",
                $end->bairro,
                true
            );
            $this->dom->addChild(
                $endereco,
                "CodigoMunicipio",
                $end->codigomunicipio,
                true
            );
            $this->dom->addChild(
                $endereco,
                "Uf",
                $end->uf,
                true
            );
            $this->dom->addChild(
                $endereco,
                "Cep",
                $end->cep,
                true
            );
            $node->appendChild($endereco);
        }
        if (!empty($tom->telefone) || !empty($tom->email)) {
            $contato = $this->dom->createElement('Contato');
            $this->dom->addChild(
                $contato,
                "Telefone",
                isset($tom->telefone) ? $tom->telefone : null,
                false
            );
            $this->dom->addChild(
                $contato,
                "Email",
                isset($tom->email) ? $tom->email : null,
                false
            );
            $node->appendChild($contato);
        }
        $parent->appendChild($node);
    }

    /**
     * Includes Intermediario TAG in parent NODE
     * @param DOMNode $parent
     */
    protected function addIntermediario(&$parent)
    {
        if (!isset($this->std->intermediarioservico)) {
            return;
        }
        $int = $this->std->intermediarioservico;
        $node = $this->dom->createElement('Intermediario');
        $ide = $this->dom->createElement('IdentificacaoIntermediario');

        $this->dom->addChild(
            $ide,
            "RazaoSocial",
            $int->razaosocial,
            true
        );
        $cpfcnpj = $this->dom->createElement('CpfCnpj');
        if (isset($int->cnpj)) {
            $this->dom->addChild(
                $cpfcnpj,
                "Cnpj",
                $int->cnpj,
                true
            );
        } else {
            $this->dom->addChild(
                $cpfcnpj,
                "Cpf",
                $int->cpf,
                true
            );
        }
        $ide->appendChild($cpfcnpj);
        $this->dom->addChild(
            $ide,
            "InscricaoMunicipal",
            $int->inscricaomunicipal,
            false
        );
        $node->appendChild($ide);
        $parent->appendChild($node);
    }

    /**
     * Includes Construcao TAG in parent NODE
     * @param DOMNode $parent
     */
    protected function addConstrucao(&$parent)
    {
        if (!isset($this->std->construcaocivil)) {
            return;
        }
        $obra = $this->std->construcaocivil;
        $node = $this->dom->createElement('ConstrucaoCivil');
        $this->dom->addChild(
            $node,
            "CodigoObra",
            isset($obra->codigoobra) ? $obra->codigoobra : null,
            true
        );
        $this->dom->addChild(
            $node,
            "Art",
            $obra->art,
            true
        );
        $parent->appendChild($node);
    }

    /**
     * Includes OrgaoGerador TAG in parent NODE
     * @param DOMNode $parent
     */
    protected function addOrgaoGerador(&$parent)
    {
        if (!isset($this->std->orgaogerador)) {
            return;
        }
        $orgao = $this->std->orgaogerador;
        $node = $this->dom->createElement('OrgaoGerador');
        $this->dom->addChild(
            $node,
            "CodigoMunicipio",
            $orgao->codigomunicipio,
            true
        );
        $this->dom->addChild(
            $node,
            "Uf",
            $orgao->uf,
            true
        );
        $parent->appendChild($node);
    }
}
